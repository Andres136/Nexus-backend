<?php

namespace App\Services\contabilidad;

use Illuminate\Support\Facades\DB;

class CostoeService
{
    //
 public function utilidad($productoId = null, $search = null, $fechaInicio = null, $fechaFin = null)
{
    $query = DB::table('orden__compra__detalles as ocd')
        ->joinSub(
            DB::table('detalles_factura_compra')
                ->selectRaw('
                    producto_id,

                    -- 🔥 COSTO PROMEDIO REAL ACTUAL
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

            -- 🔹 TOTAL VENDIDO
            SUM(ocd.cantidad_ejecutada_kg) as total_kg_vendidos,

            -- 🔹 INGRESOS
            SUM(ocd.valor_total) as ingreso,

            -- 🔹 COSTO PROMEDIO
            cp.costo_promedio,

            -- 🔹 COSTO TOTAL
            SUM(
                ocd.cantidad_ejecutada_kg * cp.costo_promedio
            ) as costo,

            -- 🔹 UTILIDAD BRUTA
            SUM(
                ocd.valor_total -
                (ocd.cantidad_ejecutada_kg * cp.costo_promedio)
            ) as utilidad,

            -- 🔹 MARGEN %
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
    | FILTRO PRODUCTO
    |--------------------------------------------------------------------------
    */
    if ($productoId) {
        $query->where('ocd.product_id', $productoId);
    }

    /*
    |--------------------------------------------------------------------------
    | FILTRO BÚSQUEDA
    |--------------------------------------------------------------------------
    */
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('p.name', 'like', "%{$search}%")
              ->orWhere('p.description', 'like', "%{$search}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FILTRO FECHAS
    |--------------------------------------------------------------------------
    */
    if ($fechaInicio && $fechaFin) {
        $query->whereBetween('ocd.created_at', [$fechaInicio, $fechaFin]);
    }

    /*
    |--------------------------------------------------------------------------
    | ORDENAR POR MAYOR UTILIDAD
    |--------------------------------------------------------------------------
    */
    $query->orderByDesc('utilidad');

    return $query->get();
}
}