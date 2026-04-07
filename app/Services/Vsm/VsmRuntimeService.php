<?php

namespace App\Services\Vsm;


use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Vsm\Alistamiento;
use App\Models\Vsm\AlistamientoDetalle;
use App\Models\Vsm\AlistamientoUsuario;
use App\Models\Vsm\AlistamientoUsuarioDetalle;
use Illuminate\Support\Facades\DB;

class VsmRuntimeService
{
    /**
     * Distribuir el tiempo total del alistamiento entre sus detalles proporcionalmente.
     */
        public function distribuirTiempoPorDetalles(Alistamiento $alist)
{
    $tiempoTotal = $alist->duracion_segundos ?? 0;
    $detalles = $alist->detalles;

    if ($tiempoTotal <= 0 || $detalles->isEmpty()) {
        return;
    }

    // Base: unidades alistadas o programadas
    $totalUnidades = $detalles->sum(function ($d) {
        return $d->cantidad_alistada > 0 
            ? $d->cantidad_alistada
            : $d->cantidad_programada;
    });

    if ($totalUnidades <= 0) {
        return;
    }

    $acumulado = 0;
    $ultimo = $detalles->count() - 1;

    foreach ($detalles as $index => $d) {

        $base = $d->cantidad_alistada > 0 
            ? $d->cantidad_alistada
            : $d->cantidad_programada;

        if ($base <= 0) {
            $d->tiempo_parcial_segundos = 0;
            $d->save();
            continue;
        }

        if ($index === $ultimo) {
            $tParcial = $tiempoTotal - $acumulado;  // corregir redondeo
        } else {
            $proporcion = $base / $totalUnidades;
            $tParcial = (int) round($tiempoTotal * $proporcion);
            $acumulado += $tParcial;
        }

        $d->tiempo_parcial_segundos = $tParcial;
        $d->save();
    }
}


    public function getKpiProductividad($sedeId)
    {
        $usuarios = AlistamientoUsuario::with('usuario')
            ->whereHas('alistamiento.ordenTrabajo.ordenCompra', function ($q) use ($sedeId) {
                $q->where('sede_id', $sedeId);
            })
            ->get();

        $data = $usuarios->map(function ($pivot) {

            $produccion = AlistamientoUsuarioDetalle::where([
                'alistamiento_id' => $pivot->alistamiento_id,
                'usuario_id' => $pivot->usuario_id
            ])->sum('cantidad_alistada');

            $tiempo = $pivot->tiempo_segundos ?? 0;

            $bolsasHora = $tiempo > 0 ? ($produccion / $tiempo) * 3600 : 0;

            $metaHora = 705;

            $rendimiento = $metaHora > 0 ? ($bolsasHora / $metaHora) * 100 : 0;

            return [
                'usuario_id' => $pivot->usuario_id,
                'usuario' => $pivot->usuario->name,
                'produccion' => $produccion,
                'tiempo_segundos' => $tiempo,
                'bolsas_hora' => round($bolsasHora, 2),
                'rendimiento' => round($rendimiento, 1),
            ];
        });

        return [
            'usuarios' => $data,
            'total_produccion' => $data->sum('produccion'),
            'promedio_rendimiento' => round($data->avg('rendimiento'), 1),
        ];
    }


    public function obtenerEficienciaPersonal($filtros)
{
    $user = auth()->user();

// 👇 fallback automático
$sedeId = $filtros['sede_id'] ?? $user->sede_id;

    $fechaInicio = $filtros['fecha_inicio'] ?? null;
$fechaFin = $filtros['fecha_fin'] ?? null;
$mes = $filtros['mes'] ?? null;
$anio = $filtros['anio'] ?? now()->year;

// 👉 SI NO VIENEN FECHAS, USAR MES
if (!$fechaInicio && !$fechaFin) {

    if ($mes) {
        // mes enviado (1-12)
        $fechaInicio = \Carbon\Carbon::now()->year($anio)->month($mes)->startOfMonth();
        $fechaFin = \Carbon\Carbon::now()->year($anio)->month($mes)->endOfMonth();
    } else {
        // mes actual
        $fechaInicio = now()->startOfMonth();
        $fechaFin = now()->endOfMonth();
    }
}
    $query = Alistamiento::with([
        'ordenTrabajo.ordenCompra.sede',
        'usuarios',
        'detalles',
    ])->where('estado', 'FINALIZADO');

    // filtro por sede
    if ($sedeId) {
        $query->whereHas('ordenTrabajo.ordenCompra', function ($q) use ($sedeId) {
            $q->where('sede_id', $sedeId);
        });
    }

    // filtro por rango de fechas
    if ($fechaInicio && $fechaFin) {
        $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    $alistamientos = $query->get();

    $resultado = [];

foreach ($alistamientos as $alist) {
    foreach ($alist->usuarios as $usuario) {

        // 👇 FILTRO CLAVE
        if ($sedeId && $usuario->sede_id != $sedeId) {
            continue;
        }

        $userId = $usuario->id;

        if (!isset($resultado[$userId])) {
            $resultado[$userId] = [
                'usuario_id' => $usuario->id,
                'nombre' => $usuario->name,
                'produccion_total' => 0,
                'tiempo_total_segundos' => 0,
            ];
        }

        $resultado[$userId]['produccion_total'] += $this->obtenerProduccionUsuarioEnAlistamiento($alist->id, $userId);
        $resultado[$userId]['tiempo_total_segundos'] += max(0, (int) $usuario->pivot->tiempo_segundos);
    }
}

    $metaDiaria = 6000;
    $horasTurno = 8.5;
    $metaPorHora = $metaDiaria / $horasTurno;

    foreach ($resultado as &$item) {
        $horas = $item['tiempo_total_segundos'] > 0
            ? $item['tiempo_total_segundos'] / 3600
            : 0;

        $bolsasPorHora = $item['tiempo_total_segundos'] > 0
            ? ($item['produccion_total'] * 3600) / $item['tiempo_total_segundos']
            : 0;

        $eficiencia = $metaPorHora > 0
            ? ($bolsasPorHora / $metaPorHora) * 100
            : 0;

        if ($eficiencia >= 100) {
            $estado = 'EFICIENTE';
        } elseif ($eficiencia >= 80) {
            $estado = 'NORMAL';
        } else {
            $estado = 'BAJO';
        }

        $item['horas'] = round($horas, 2);
        $item['bolsas_por_hora'] = round($bolsasPorHora, 2);
        $item['eficiencia_porcentaje'] = round($eficiencia, 2);
        $item['estado'] = $estado;
    }

    return array_values($resultado);
}         
private function obtenerProduccionUsuarioEnAlistamiento($alistId, $userId)
{
    return AlistamientoUsuarioDetalle::where('alistamiento_id', $alistId)
        ->where('usuario_id', $userId)
        ->sum('cantidad_alistada');
}
}
