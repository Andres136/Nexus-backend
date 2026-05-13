<?php

namespace App\Services\contabilidad;

use Illuminate\Support\Facades\DB;

class CostoeService
{
    public function utilidad(
        $productoId = null,
        $search = null,
        $fechaInicio = null,
        $fechaFin = null,
        $perPage = 50
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
            ->join(
                'products as p',
                'p.id',
                '=',
                'ocd.product_id'
            )
            ->selectRaw('
                ocd.product_id,
                p.name,
                p.description,

                SUM(ocd.cantidad_ejecutada_kg)
                    as total_kg_vendidos,

                SUM(ocd.valor_total)
                    as ingreso,

                cp.costo_promedio,

                SUM(
                    ocd.cantidad_ejecutada_kg *
                    cp.costo_promedio
                ) as costo,

                SUM(
                    ocd.valor_total -
                    (
                        ocd.cantidad_ejecutada_kg *
                        cp.costo_promedio
                    )
                ) as utilidad,

                (
                    SUM(
                        ocd.valor_total -
                        (
                            ocd.cantidad_ejecutada_kg *
                            cp.costo_promedio
                        )
                    ) / NULLIF(
                        SUM(ocd.valor_total),
                        0
                    )
                ) * 100 as margen_porcentaje
            ')
            ->groupBy(
                'ocd.product_id',
                'p.name',
                'p.description',
                'cp.costo_promedio'
            )
            ->havingRaw(
                'SUM(ocd.cantidad_ejecutada_kg) > 0'
            );

        /*
        |--------------------------------------------------------------------------
        | FILTROS
        |--------------------------------------------------------------------------
        */
        if ($productoId) {
            $query->where(
                'ocd.product_id',
                $productoId
            );
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where(
                    'p.name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'p.description',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween(
                'ocd.created_at',
                [
                    $fechaInicio,
                    $fechaFin
                ]
            );
        }

        $query->orderByDesc('utilidad');

        /*
        |--------------------------------------------------------------------------
        | RESUMEN GLOBAL (SIN PAGINAR)
        |--------------------------------------------------------------------------
        */
        $resumenData = (clone $query)->get();

        $resumen = [
            'total_productos' =>
                $resumenData->count(),

            'total_kg_vendidos' =>
                $resumenData->sum(
                    'total_kg_vendidos'
                ),

            'total_ingreso' =>
                $resumenData->sum(
                    'ingreso'
                ),

            'total_costo' =>
                $resumenData->sum(
                    'costo'
                ),

            'total_utilidad' =>
                $resumenData->sum(
                    'utilidad'
                ),

            'margen_global' =>
                $resumenData->sum(
                    'ingreso'
                ) > 0
                    ? (
                        (
                            $resumenData->sum(
                                'utilidad'
                            ) /
                            $resumenData->sum(
                                'ingreso'
                            )
                        ) * 100
                    )
                    : 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | DETALLE PAGINADO
        |--------------------------------------------------------------------------
        */
        $detalle = $query->paginate($perPage);

        return [
            'detalle' => $detalle,
            'resumen' => $resumen,
        ];
    }
}