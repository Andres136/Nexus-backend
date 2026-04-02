<?php

namespace App\Services\Vsm;

use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Vsm\Alistamiento;
use App\Models\Vsm\AlistamientoDetalle;
use App\Models\Vsm\AlistamientoTiempo;
use App\Models\Vsm\AlistamientoUsuario;
use App\Models\Vsm\AlistamientoUsuarioDetalle;
use Illuminate\Support\Facades\DB;

class AlistamientoService
{
public function crearAlistamiento($data, $usuarioAuthId)
{
    return DB::transaction(function () use ($data, $usuarioAuthId) {

        // 🧱 1. CABECERA
        $alist = Alistamiento::create([
            'orden_trabajo_id' => $data['orden_trabajo_id'],
            'usuario_id'       => $usuarioAuthId,
            'cantidad'         => $data['cantidad'],
            'estado'           => 'INICIADO',
            'fecha'            => now(),
        ]);

        // 🔥 🔥 🔥 HORA BASE (UNA SOLA VEZ)
        $inicioGlobal = now();

        // 🧑‍🤝‍🧑 2. USUARIOS
        foreach ($data['usuarios'] as $usuarioId) {
            AlistamientoUsuario::create([
                'alistamiento_id' => $alist->id,
                'usuario_id'      => $usuarioId,
                'estado'          => 'EN_PROGRESO',
                'inicio'          => $inicioGlobal, // 🔥 MISMA HORA PARA TODOS
                'tiempo_segundos' => 0,
            ]);
        }

        // 📦 3. DETALLES
        $ordenTrabajo = OrdenDeTrabajo::with('ordenCompra.detalles')
            ->findOrFail($data['orden_trabajo_id']);

        foreach ($ordenTrabajo->ordenCompra->detalles as $item) {

            $detalle = AlistamientoDetalle::create([
                'alistamiento_id'      => $alist->id,
                'product_id'           => $item->product_id,
                'cantidad_programada'  => $item->cantidad,
                'cantidad_alistada'    => 0,
                'cantidad_faltante'    => $item->cantidad,
            ]);

            foreach ($data['usuarios'] as $usuarioId) {
                AlistamientoUsuarioDetalle::create([
                    'alistamiento_id'   => $alist->id,
                    'usuario_id'        => $usuarioId,
                    'detalle_id'        => $detalle->id,
                    'cantidad_alistada' => 0,
                ]);
            }
        }

        // 🕒 4. EVENTO
        AlistamientoTiempo::create([
            'alistamiento_id' => $alist->id,
            'tipo'            => 'INICIO',
            'fecha_hora'      => $inicioGlobal, // 🔥 MISMA REFERENCIA
        ]);

        return $alist;
    });
}

public function pausarUsuario($alistId, $userId, $razon = null)
{
    $pivot = AlistamientoUsuario::where('alistamiento_id', $alistId)
        ->where('usuario_id', $userId)
        ->firstOrFail();

    if ($pivot->estado === 'PAUSADO') {
        throw new \Exception("Ya está pausado");
    }

    // 🔥 Guardar tiempo trabajado
    if ($pivot->inicio) {
        $pivot->tiempo_segundos += now()->diffInSeconds($pivot->inicio);
    }

    // 🔥 Registrar evento con razón
    AlistamientoTiempo::create([
        'alistamiento_id' => $alistId,
        'tipo'            => 'PAUSA',
        'fecha_hora'      => now(),
        'razon'           => $razon,
    ]);

    // 🔥 Actualizar pivot
    $pivot->estado = 'PAUSADO';
    $pivot->pausado_en = now();
    $pivot->inicio = null;

    $pivot->save();

    return $pivot;
}


public function reanudarUsuario($alistId, $userId)
{
    $pivot = AlistamientoUsuario::where('alistamiento_id', $alistId)
        ->where('usuario_id', $userId)
        ->firstOrFail();

    if ($pivot->estado !== 'PAUSADO') {
        throw new \Exception("No está pausado");
    }

    // 🔥 Evento
    AlistamientoTiempo::create([
        'alistamiento_id' => $alistId,
        'tipo' => 'REANUDACION',
        'fecha_hora'      => now(),
    ]);

    $pivot->estado = 'EN_PROGRESO';
    $pivot->inicio = now();
    $pivot->pausado_en = null;

    $pivot->save();

    return $pivot;
}

public function finalizarAlistamiento($alistId)
{
    return DB::transaction(function () use ($alistId) {

        $alist = Alistamiento::with(['usuarios', 'detalles'])->findOrFail($alistId);

        // 🔥 1. Cerrar usuarios activos
     foreach ($alist->usuarios as $usuario) {

    $pivot = $usuario->pivot;

    if ($pivot->estado === 'EN_PROGRESO' && $pivot->inicio) {

        $tiempoActual = now()->diffInSeconds($pivot->inicio);

        $pivot->tiempo_segundos =
            max(0, (int) $pivot->tiempo_segundos) + max(0, $tiempoActual);

        $pivot->inicio = null;
    }

    // 🔥 blindaje total
    $pivot->tiempo_segundos = max(0, (int) $pivot->tiempo_segundos);

    $pivot->estado = 'FINALIZADO';
    $pivot->save();
}

        // 🔥 2. Tiempo total REAL
        $tiempoTotal = $this->calcularTiempoTotal($alist);

        // 🔥 3. Producción
        $totalAlistado = $alist->detalles->sum('cantidad_alistada');
        $totalProgramado = $alist->detalles->sum('cantidad_programada');

        // 🔥 4. KPI
        $horas = $tiempoTotal > 0 ? ($tiempoTotal / 3600) : 0;
        $bolsasPorHora = $horas > 0 ? ($totalAlistado / $horas) : 0;

        // 🎯 META
        $metaDiaria = 6000;
        $horasTurno = 8.5;
        $metaPorHora = $metaDiaria / $horasTurno; // 706

        // 🔥 5. Cumplimiento
        $cumplimiento = $metaDiaria > 0
            ? ($totalAlistado / $metaDiaria) * 100
            : 0;

        // 🔥 6. Estado rendimiento
        if ($bolsasPorHora >= $metaPorHora) {
            $estado = 'EFICIENTE';
        } elseif ($bolsasPorHora >= 600) {
            $estado = 'RIESGO';
        } else {
            $estado = 'BAJO';
        }

        // 🔥 7. Evento
        AlistamientoTiempo::create([
            'alistamiento_id' => $alistId,
            'tipo' => 'FINALIZACION',
            'fecha_hora' => now(),
        ]);

        // 🔥 8. Guardar en BD
        $alist->update([
            'estado' => 'FINALIZADO',
            'duracion_segundos' => $tiempoTotal,
            'rendimiento_bolsas_hora' => round($bolsasPorHora, 2),
            'cumplimiento_porcentaje' => round($cumplimiento, 2),
        ]);

        return [
            'tiempo_segundos' => $tiempoTotal,
            'horas' => round($horas, 2),
            'bolsas_alistadas' => $totalAlistado,
            'bolsas_por_hora' => round($bolsasPorHora, 2),
            'cumplimiento' => round($cumplimiento, 2),
            'estado' => $estado,
        ];
    });
}


public function calcularTiempoTotal($alist)
{
    $total = 0;

    foreach ($alist->usuarios as $usuario) {

        $pivot = $usuario->pivot;

        // 🔥 base limpio
        $tiempo = max(0, (int) $pivot->tiempo_segundos);

        // 🔥 si sigue activo
        if ($pivot->estado === 'EN_PROGRESO' && $pivot->inicio) {

            $tiempoActual = now()->diffInSeconds($pivot->inicio);

            $tiempo += max(0, $tiempoActual);
        }

        $total += $tiempo;
    }

    return max(0, $total);
}

public function registrarProduccion($alistId, $detalleId, $cantidad, $userId)
{
    return DB::transaction(function () use ($alistId, $detalleId, $cantidad, $userId) {

        if (!is_numeric($cantidad)) {
            throw new \Exception("Cantidad inválida");
        }

        $cantidad = (int) $cantidad;

        $pivot = AlistamientoUsuario::where([
            'alistamiento_id' => $alistId,
            'usuario_id'      => $userId
        ])->firstOrFail();

        if ($pivot->estado !== 'EN_PROGRESO') {
            throw new \Exception("No puedes registrar producción si estás en pausa");
        }

        $registro = AlistamientoUsuarioDetalle::where([
            'alistamiento_id' => $alistId,
            'usuario_id'      => $userId,
            'detalle_id'      => $detalleId,
        ])->firstOrFail();

        $detalle = AlistamientoDetalle::findOrFail($detalleId);

        if ($detalle->cantidad_alistada + $cantidad > $detalle->cantidad_programada) {
            throw new \Exception("Excede la cantidad programada");
        }

        // 🔥 UPDATE USUARIO
        AlistamientoUsuarioDetalle::where('id', $registro->id)
            ->increment('cantidad_alistada', $cantidad);

        // 🔥 UPDATE GLOBAL
        AlistamientoDetalle::where('id', $detalleId)
            ->increment('cantidad_alistada', $cantidad);

        $detalle->refresh();

        $detalle->cantidad_faltante = max(
            0,
            $detalle->cantidad_programada - $detalle->cantidad_alistada
        );

        $detalle->save();

        return [
            'usuario_id' => $userId,
            'produccion_usuario' => $registro->cantidad_alistada + $cantidad,
            'total_producto' => $detalle->cantidad_alistada,
            'faltante' => $detalle->cantidad_faltante
        ];
    });
}
}