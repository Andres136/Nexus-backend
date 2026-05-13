<?php

namespace App\Services\contabilidad;

use Illuminate\Support\Facades\DB;

class CostoeService
{
    //
public function utilidad(
    $productoId = null,
    $search = null,
    $fechaInicio = null,
    $fechaFin = null
) {
    $query = DB::table('orden__compra__detalles as ocd')
        ->joinSub(
            DB::table('detalles_factura_compra')
                ->selectRaw('
                    producto_id,
                    SUM(total) / NULLIF(SUM(cantidad), 0) as costo_promedio
                ')
                ->groupBy('producto_id'),
            'cp',
            'cp.producto_id',
            '=',
            'ocd.product_id'
        )
        ->join('products as p', 'p.id', '=', 'ocd.product_id')
        ->selectRaw('
            ocd.product_id,
            p.name,
            p.description,

            SUM(ocd.cantidad_ejecutada_kg) as total_kg_vendidos,
            SUM(ocd.valor_total) as ingreso,

            cp.costo_promedio,

            SUM(
                ocd.cantidad_ejecutada_kg * cp.costo_promedio
            ) as costo,

            SUM(
                ocd.valor_total -
                (ocd.cantidad_ejecutada_kg * cp.costo_promedio)
            ) as utilidad,

            (
                SUM(
                    ocd.valor_total -
                    (ocd.cantidad_ejecutada_kg * cp.costo_promedio)
                ) / NULLIF(SUM(ocd.valor_total), 0)
            ) * 100 as margen_porcentaje
        ')
        ->groupBy(
            'ocd.product_id',
            'p.name',
            'p.description',
            'cp.costo_promedio'
        )
        ->havingRaw('SUM(ocd.cantidad_ejecutada_kg) > 0');

    /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */
    if ($productoId) {
        $query->where('ocd.product_id', $productoId);
    }

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('p.name', 'like', "%{$search}%")
              ->orWhere('p.description', 'like', "%{$search}%");
        });
    }

    if ($fechaInicio && $fechaFin) {
        $query->whereBetween('ocd.created_at', [
            $fechaInicio,
            $fechaFin
        ]);
    }

    $query->orderByDesc('utilidad');

    // 🔥 RESULTADOS DETALLADOS
    $resultados = $query->get();

    /*
    |--------------------------------------------------------------------------
    | TOTALES GENERALES FILTRADOS
    |--------------------------------------------------------------------------
    */
    $resumen = [
        'total_productos' => $resultados->count(),

        'total_kg_vendidos' => $resultados->sum('total_kg_vendidos'),

        'total_ingreso' => $resultados->sum('ingreso'),

        'total_costo' => $resultados->sum('costo'),

        'total_utilidad' => $resultados->sum('utilidad'),

        'margen_global' => $resultados->sum('ingreso') > 0
            ? (
                ($resultados->sum('utilidad') /
                 $resultados->sum('ingreso')) * 100
            )
            : 0,
    ];

    return [
        'detalle' => $resultados,
        'resumen' => $resumen,
    ];
}
}