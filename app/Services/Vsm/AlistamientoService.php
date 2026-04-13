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
    $pivot->razon = $razon;

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

        $alist = Alistamiento::with(['usuarios', 'detalles', 'tiempos'])->findOrFail($alistId);

        //  1. Cerrar usuarios
        foreach ($alist->usuarios as $usuario) {

            $pivot = $usuario->pivot;

            if ($pivot->estado === 'EN_PROGRESO' && $pivot->inicio) {

             $tiempoActual = now()->timestamp - strtotime($pivot->inicio);

                $pivot->tiempo_segundos =
                    max(0, (int) $pivot->tiempo_segundos) + max(0, $tiempoActual);

                $pivot->inicio = null;
            }

            $pivot->tiempo_segundos = max(0, (int) $pivot->tiempo_segundos);
            $pivot->estado = 'FINALIZADO';
            $pivot->save();
        }

        //  2. CREAR EVENTO FINALIZACION (ANTES DEL CÁLCULO)
        AlistamientoTiempo::create([
            'alistamiento_id' => $alistId,
            'tipo' => 'FINALIZACION',
            'fecha_hora' => now(),
        ]);

        //  3. RECARGAR EVENTOS
        $alist->load('tiempos');

        //  4. CALCULAR TIEMPO
$tiempoTotal = 0;

foreach ($alist->usuarios as $usuario) {
    $tiempoUsuario = max(0, (int) $usuario->pivot->tiempo_segundos);

    if ($tiempoUsuario > $tiempoTotal) {
        $tiempoTotal = $tiempoUsuario;
    }
}

        

        //  5. PRODUCCIÓN
        $totalAlistado = $alist->detalles->sum('cantidad_alistada');

foreach ($alist->detalles as $detalle) {

    if ($totalAlistado > 0) {

        $proporcion = $detalle->cantidad_alistada / $totalAlistado;
        $detalle->tiempo_parcial_segundos = (int) ($tiempoTotal * $proporcion);

    } else {
        //  REPARTIR EQUITATIVO
        $detalle->tiempo_parcial_segundos = (int) ($tiempoTotal / max(1, $alist->detalles->count()));
    }

    $detalle->save();
}

        //  6. KPI (forma correcta)
        $bolsasPorHora = $tiempoTotal > 0
            ? ($totalAlistado * 3600) / $tiempoTotal
            : 0;

        $horas = $tiempoTotal / 3600;

        // 🔥 7. META
        $metaDiaria = 6000;
        $horasTurno = 8.5;
        $metaPorHora = $metaDiaria / $horasTurno;

        $cumplimiento = $metaDiaria > 0
            ? ($totalAlistado / $metaDiaria) * 100
            : 0;

        //  8. ESTADO
        if ($bolsasPorHora >= $metaPorHora) {
            $estado = 'EFICIENTE';
        } elseif ($bolsasPorHora >= 600) {
            $estado = 'RIESGO';
        } else {
            $estado = 'BAJO';
        }

        //  9. GUARDAR
        $alist->update([
            'estado' => 'FINALIZADO',
            'duracion_segundos' => (int) $tiempoTotal,
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

public function obtenerAlistamientosActivos($user, $sedeIdFiltro)
{
    $sedeId = $sedeIdFiltro ?? $user->sede_id;

$query = Alistamiento::with([
    'ordenTrabajo.ordenCompra.cliente',
    'ordenTrabajo.ordenCompra.sede',
    'usuarios' => function ($q) use ($sedeId) {
        $q->where('sede_id', $sedeId);
    },
    'tiempos',
    'detalles.product'
])
->whereIn('estado', ['INICIADO', 'PAUSADO', 'REANUDADO'])
->whereHas('usuarios', function ($q) use ($sedeId) {
    $q->where('sede_id', $sedeId);
});
    $alistamientos = $query
        ->orderBy('updated_at', 'desc')
        ->get()
        ->map(function ($alist) {
 $usuarios = $alist->usuarios->map(function ($u) {
    $pivot = $u->pivot;
    $tiempo = max(0, (int) $pivot->tiempo_segundos);

   if (in_array($pivot->estado, ['EN_PROGRESO', 'REANUDADO']) && $pivot->inicio) {
        $inicio = \Carbon\Carbon::parse($pivot->inicio);
        $tiempo += max(0, $inicio->diffInSeconds(now()));
    }

    return [
        'id' => $u->id,
        'name' => $u->name,
        'estado' => $pivot->estado,
        'inicio_usuario' => $pivot->inicio,
        'pausado_en' => $pivot->pausado_en,
        'segundos_usuario' => $tiempo,
    ];
});
            $tiempoTotal = $usuarios->max('segundos_usuario') ?? 0;
            $detalles = $alist->detalles->map(function ($d) {
                return [
                    'id' => $d->id,
                    'product_id' => $d->product_id,
                    'product' => $d->product->name ?? null,
                    'programada' => $d->cantidad_programada,
                    'alistada' => $d->cantidad_alistada,
                    'faltante' => $d->cantidad_faltante,
                ];
            });
            return [
                'id' => $alist->id,
                'orden_trabajo_id' => $alist->orden_trabajo_id,
                'estado' => $alist->estado,
                'inicio' => $alist->inicio,
                'segundos_transcurridos' => $tiempoTotal,
                'usuarios' => $usuarios,
                'detalles' => $detalles,
                'orden_trabajo' => $alist->ordenTrabajo,
                'sede' => [
                    'id' => $alist->ordenTrabajo->ordenCompra->sede->id,
                    'nombre' => $alist->ordenTrabajo->ordenCompra->sede->nombre,
                ],
                'cliente' => [
                    'id' => $alist->ordenTrabajo->ordenCompra->cliente->id,
                    'nombre' => $alist->ordenTrabajo->ordenCompra->cliente->nombre,
                ]
            ];
        });

    $alistamientos = $alistamientos
        ->sortBy(fn($a) => $a['sede']['nombre'])
        ->values()
        ->all();

    return $alistamientos;
}



public function calcularTiempoTotal($alist)
{
    $tiempos = [];

    foreach ($alist->usuarios as $usuario) {

        $pivot = $usuario->pivot;

        $tiempo = max(0, (int) $pivot->tiempo_segundos);

        if ($pivot->estado === 'EN_PROGRESO' && $pivot->inicio) {
            $tiempoActual = now()->diffInSeconds($pivot->inicio);
            $tiempo += max(0, $tiempoActual);
        }

        $tiempos[] = $tiempo;
    }

    return count($tiempos) ? max($tiempos) : 0;
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