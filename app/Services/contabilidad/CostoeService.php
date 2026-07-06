<?php

namespace App\Services\contabilidad;

use App\EstadoEnum;
use Illuminate\Support\Facades\DB;

class CostoeService
{
    public function utilidad(
        $productoId = null,
        $empresaId = null,
        $search = null,
        $fechaInicio = null,
        $fechaFin = null,
        $perPage = 50,
        $paginar = true
    ) {
        $costosPromedio = DB::table('detalles_factura_compra as dfc')
            ->join('factura_compras as fc', 'fc.id', '=', 'dfc.factura_compra_id')
            ->selectRaw('
                dfc.producto_id,
                SUM(dfc.cantidad * COALESCE(dfc.precio_unitario, 0))
                    / NULLIF(SUM(dfc.cantidad), 0) as costo_promedio
            ')
            ->where('fc.estado_id', '!=', EstadoEnum::ANULADA->value)
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where('fc.empresa_id', $empresaId);
            })
            ->groupBy('dfc.producto_id');

        // 🔹 Lo comprado en el periodo (por fecha de emisión de la factura de compra)
        $comprasPeriodo = DB::table('detalles_factura_compra as dfc')
            ->join('factura_compras as fc', 'fc.id', '=', 'dfc.factura_compra_id')
            ->selectRaw('
                dfc.producto_id,
                SUM(dfc.cantidad) as kg_comprado,
                SUM(dfc.cantidad * COALESCE(dfc.precio_unitario, 0)) as costo_comprado
            ')
            ->where('fc.estado_id', '!=', EstadoEnum::ANULADA->value)
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where('fc.empresa_id', $empresaId);
            })
            ->when($productoId, function ($query) use ($productoId) {
                $query->where('dfc.producto_id', $productoId);
            })
            ->when($fechaInicio && $fechaFin, function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereDate('fc.fecha_emision', '>=', $fechaInicio)
                    ->whereDate('fc.fecha_emision', '<=', $fechaFin);
            })
            ->when($fechaInicio && !$fechaFin, function ($query) use ($fechaInicio) {
                $query->whereDate('fc.fecha_emision', '>=', $fechaInicio);
            })
            ->when(!$fechaInicio && $fechaFin, function ($query) use ($fechaFin) {
                $query->whereDate('fc.fecha_emision', '<=', $fechaFin);
            })
            ->groupBy('dfc.producto_id');

        $query = DB::table('orden__compra__detalles as ocd')
            ->join('orden__compras as oc', 'oc.id', '=', 'ocd.orden_compra_id')
            ->joinSub(
                $costosPromedio,
                'cp',
                'cp.producto_id',
                '=',
                'ocd.product_id'
            )
            ->leftJoinSub(
                $comprasPeriodo,
                'cpp',
                'cpp.producto_id',
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

                SUM(ocd.cantidad * COALESCE(ocd.valor_unitario, 0))
                    as ingreso,

                cp.costo_promedio,

                SUM(
                    ocd.cantidad_ejecutada_kg *
                    cp.costo_promedio
                ) as costo,

                SUM(
                    (ocd.cantidad * COALESCE(ocd.valor_unitario, 0)) -
                    (
                        ocd.cantidad_ejecutada_kg *
                        cp.costo_promedio
                    )
                ) as utilidad,

                (
                    SUM(
                        (ocd.cantidad * COALESCE(ocd.valor_unitario, 0)) -
                        (
                            ocd.cantidad_ejecutada_kg *
                            cp.costo_promedio
                        )
                    ) / NULLIF(
                        SUM(ocd.cantidad * COALESCE(ocd.valor_unitario, 0)),
                        0
                    )
                ) * 100 as margen_porcentaje,

                MAX(COALESCE(cpp.kg_comprado, 0)) as kg_comprado,
                MAX(COALESCE(cpp.costo_comprado, 0)) as costo_comprado
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

        if ($empresaId) {
            $query->where('oc.empresa_id', $empresaId);
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
            $query
                ->whereDate('ocd.created_at', '>=', $fechaInicio)
                ->whereDate('ocd.created_at', '<=', $fechaFin);
        } elseif ($fechaInicio) {
            $query->whereDate('ocd.created_at', '>=', $fechaInicio);
        } elseif ($fechaFin) {
            $query->whereDate('ocd.created_at', '<=', $fechaFin);
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

            'total_kg_comprado' =>
                $resumenData->sum(
                    'kg_comprado'
                ),

            'total_costo_comprado' =>
                $resumenData->sum(
                    'costo_comprado'
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
        $detalle = $paginar
            ? $query->paginate($perPage)
            : $query->get();

        return [
            'detalle' => $detalle,
            'resumen' => $resumen,
        ];
    }
}
