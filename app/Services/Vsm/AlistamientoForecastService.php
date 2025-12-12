<?php

namespace App\Services\Vsm;

use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Vsm\Alistamiento;
use App\Models\Vsm\AlistamientoDetalle;
use Illuminate\Support\Facades\DB;

class AlistamientoForecastService
{
    const JORNADA_SEGUNDOS = 32400; // 9 horas

    /**
     * Obtener tiempo promedio por producto basado en históricos.
     */
public function tiempoPromedioPorProducto($productId)
{
    $data = AlistamientoDetalle::where('product_id', $productId)
        ->selectRaw("
            SUM(
                CASE 
                    WHEN cantidad_alistada > 0 THEN cantidad_alistada
                    ELSE cantidad_programada 
                END
            ) as total_u,
            SUM(tiempo_parcial_segundos) as total_seg
        ")
        ->first();

    if (!$data || $data->total_u == 0 || $data->total_seg == 0) {
        return null; 
    }

    return $data->total_seg / $data->total_u;
}


    /**
     * Calcular tiempo estimado para una OT completa.
     */
public function estimarTiempoOT(OrdenDeTrabajo $ot, $usuarios = 1)
{
    $tiempoTotal = 0;

    foreach ($ot->ordenCompra->detalles as $d) {

        // Determinar faltantes
        $faltantes = $d->faltantes ?? ($d->cantidad - $d->cantidad_enviada);

        if ($faltantes <= 0) continue;

        // TPU real basado en históricos
        $tpu = $this->tiempoPromedioPorProducto($d->product_id);

        if (!$tpu) {
            logger("Producto {$d->product_id} sin TPU. No se calcula tiempo estimado para este item.");
            continue;
        }

        // Calcular tiempo para este producto
        $tiempoTotal += ($faltantes * $tpu);
    }

    // Ajustar por usuarios asignados
    $tiempoReal = $tiempoTotal / max(1, $usuarios);

    return [
        'segundos_totales'     => (int)$tiempoTotal,
        'segundos_por_usuario' => (int)$tiempoReal,
        'horas'                => round($tiempoReal / 3600, 2),
        'dias'                 => round($tiempoReal / self::JORNADA_SEGUNDOS, 2),
    ];
}


    /**
     * Calcular pronóstico para TODAS las órdenes pendientes (estado 2).
     */
public function pronosticoGlobal($usuarios = 1)
{
    $ordenes = OrdenDeTrabajo::where('estado_id', 1)
        ->with([
            'ordenCompra.detalles.product',
            'ordenCompra.cliente'
        ])
        ->distinct()
        ->get();

    return $ordenes->map(function ($ot) use ($usuarios) {

        $est = $this->estimarTiempoOT($ot, $usuarios);

        return [
            'orden_trabajo_id'   => $ot->id,
            'cliente'            => $ot->ordenCompra->cliente->nombre ?? 'Sin cliente',
            'segundos_estimados' => $est['segundos_totales'],
            'tiempo_por_usuario' => $est['segundos_por_usuario'],
            'horas_estimadas'    => $est['horas'],
            'dias_estimados'     => $est['dias'],
            'usuarios_asignados' => $usuarios,
        ];
    });
}



}
