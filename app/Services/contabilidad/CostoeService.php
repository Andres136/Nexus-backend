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
                        SUM(cantidad * precio_unitario) / SUM(cantidad) as costo_promedio
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
                SUM(ocd.cantidad_requerida_kg) as total_kg_vendidos,
                SUM(ocd.valor_total) as ingreso,
                cp.costo_promedio,
                SUM(ocd.cantidad_requerida_kg * cp.costo_promedio) as costo,
                SUM(ocd.valor_total - (ocd.cantidad_requerida_kg * cp.costo_promedio)) as utilidad
            ')
            ->groupBy('ocd.product_id', 'p.name', 'p.description', 'cp.costo_promedio');

        // 🔹 Filtro producto
        if ($productoId) {
            $query->where('ocd.product_id', $productoId);
        }

        // 🔹 Filtro búsqueda
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'like', "%$search%")
                  ->orWhere('p.description', 'like', "%$search%");
            });
        }

        // 🔹 Filtro fechas
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('ocd.created_at', [$fechaInicio, $fechaFin]);
        }

        return $query->get();
    }
}