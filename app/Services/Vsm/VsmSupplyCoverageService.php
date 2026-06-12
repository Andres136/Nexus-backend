<?php

namespace App\Services\Vsm;

use App\EstadoEnum;
use App\Models\Crm\Orden_Compra_Detalle;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use App\RolEnum;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class VsmSupplyCoverageService
{
    public function obtenerCobertura($user, array $filtros = []): array
    {
        $sedeId = $this->resolverSede($user, $filtros['sede_id'] ?? null);
        $search = mb_substr(trim((string) ($filtros['search'] ?? '')), 0, 100);
        $estado = $filtros['estado'] ?? null;
        $perPage = min(100, max(10, (int) ($filtros['per_page'] ?? 25)));

        $necesidades = $this->consultaNecesidades($sedeId, $search);
        $inventario = $this->consultaInventario($sedeId);
        $proveedor = $this->consultaProveedor($sedeId);

        $cobertura = DB::query()
            ->fromSub($necesidades, 'n')
            ->leftJoinSub($inventario, 'i', 'i.producto_id', '=', 'n.producto_id')
            ->leftJoinSub($proveedor, 'p', 'p.producto_id', '=', 'n.producto_id')
            ->selectRaw('
                n.producto_id,
                n.codigo,
                n.producto,
                n.necesidad_pendiente_kg,
                COALESCE(i.stock_fisico_kg, 0) AS stock_fisico_kg,
                COALESCE(i.stock_seguridad_kg, 0) AS stock_seguridad_kg,
                COALESCE(i.stock_utilizable_kg, 0) AS stock_utilizable_kg,
                COALESCE(p.proveedor_pendiente_kg, 0) AS proveedor_pendiente_kg,
                LEAST(n.necesidad_pendiente_kg, COALESCE(i.stock_utilizable_kg, 0)) AS cobertura_stock_kg,
                LEAST(
                    GREATEST(n.necesidad_pendiente_kg - COALESCE(i.stock_utilizable_kg, 0), 0),
                    COALESCE(p.proveedor_pendiente_kg, 0)
                ) AS cobertura_proveedor_kg,
                GREATEST(
                    n.necesidad_pendiente_kg
                    - COALESCE(i.stock_utilizable_kg, 0)
                    - COALESCE(p.proveedor_pendiente_kg, 0),
                    0
                ) AS faltante_compra_kg
            ');

        $this->aplicarFiltroEstado($cobertura, $estado);

        $resumen = DB::query()
            ->fromSub(clone $cobertura, 'c')
            ->selectRaw('
                COUNT(*) AS productos_analizados,
                SUM(CASE WHEN c.faltante_compra_kg > 0 THEN 1 ELSE 0 END) AS productos_con_faltante,
                COALESCE(SUM(c.necesidad_pendiente_kg), 0) AS necesidad_pendiente_kg,
                COALESCE(SUM(c.stock_utilizable_kg), 0) AS stock_utilizable_kg,
                COALESCE(SUM(c.proveedor_pendiente_kg), 0) AS proveedor_pendiente_kg,
                COALESCE(SUM(c.faltante_compra_kg), 0) AS faltante_compra_kg
            ')
            ->first();

        $pagina = $cobertura
            ->orderByDesc('faltante_compra_kg')
            ->orderBy('producto')
            ->paginate($perPage);

        $productoIds = collect($pagina->items())->pluck('producto_id');
        $ordenesCliente = $this->ordenesClientePorProducto($productoIds, $sedeId);
        $ordenesProveedor = $this->ordenesProveedorPorProducto($productoIds, $sedeId);

        $productos = collect($pagina->items())->map(function ($item) use ($ordenesCliente, $ordenesProveedor) {
            $necesidad = (float) $item->necesidad_pendiente_kg;
            $faltante = (float) $item->faltante_compra_kg;
            $coberturaProveedor = (float) $item->cobertura_proveedor_kg;

            return [
                'producto_id' => (int) $item->producto_id,
                'codigo' => $item->codigo ?? '-',
                'producto' => $item->producto ?? 'Sin producto',
                'necesidad_pendiente_kg' => round($necesidad, 2),
                'stock_fisico_kg' => round((float) $item->stock_fisico_kg, 2),
                'stock_seguridad_kg' => round((float) $item->stock_seguridad_kg, 2),
                'stock_utilizable_kg' => round((float) $item->stock_utilizable_kg, 2),
                'proveedor_pendiente_kg' => round((float) $item->proveedor_pendiente_kg, 2),
                'cobertura_stock_kg' => round((float) $item->cobertura_stock_kg, 2),
                'cobertura_proveedor_kg' => round($coberturaProveedor, 2),
                'faltante_compra_kg' => round($faltante, 2),
                'cobertura_porcentaje' => $necesidad > 0
                    ? round((($necesidad - $faltante) / $necesidad) * 100, 2)
                    : 100,
                'estado' => match (true) {
                    $faltante > 0 => 'FALTANTE_COMPRA',
                    $coberturaProveedor > 0 => 'CUBIERTO_CON_PROVEEDOR',
                    default => 'CUBIERTO_CON_STOCK',
                },
                'ordenes_cliente' => $ordenesCliente->get($item->producto_id, collect())->values()->all(),
                'ordenes_proveedor' => $ordenesProveedor->get($item->producto_id, collect())->values()->all(),
            ];
        });

        return [
            'resumen' => [
                'productos_analizados' => (int) ($resumen->productos_analizados ?? 0),
                'productos_con_faltante' => (int) ($resumen->productos_con_faltante ?? 0),
                'necesidad_pendiente_kg' => round((float) ($resumen->necesidad_pendiente_kg ?? 0), 2),
                'stock_utilizable_kg' => round((float) ($resumen->stock_utilizable_kg ?? 0), 2),
                'proveedor_pendiente_kg' => round((float) ($resumen->proveedor_pendiente_kg ?? 0), 2),
                'faltante_compra_kg' => round((float) ($resumen->faltante_compra_kg ?? 0), 2),
                'sede_id' => $sedeId,
            ],
            'productos' => $productos->all(),
            'paginacion' => [
                'pagina_actual' => $pagina->currentPage(),
                'ultima_pagina' => $pagina->lastPage(),
                'por_pagina' => $pagina->perPage(),
                'total' => $pagina->total(),
                'desde' => $pagina->firstItem(),
                'hasta' => $pagina->lastItem(),
            ],
            'advertencias' => [
                'La cobertura se calcula agrupada por producto y sede para no reutilizar el mismo stock.',
                'Las cantidades de proveedor se consideran kilogramos; debe validarse la unidad de compra de cada producto.',
                'No existen reservas persistidas, por lo que la cobertura representa una fotografía del momento.',
            ],
        ];
    }

    private function consultaNecesidades(?int $sedeId, string $search): Builder
    {
        return DB::table('orden__compra__detalles as d')
            ->join('orden__compras as o', 'o.id', '=', 'd.orden_compra_id')
            ->join('products as pr', 'pr.id', '=', 'd.product_id')
            ->whereIn('o.estado_id', [
                EstadoEnum::PENDIENTE->value,
                EstadoEnum::ENTREGA_PARCIAL->value,
            ])
            ->when($sedeId, fn (Builder $query) => $query->where('o.sede_id', $sedeId))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $subquery) use ($search) {
                    $subquery->where('pr.name', 'like', "%{$search}%")
                        ->orWhere('pr.code', 'like', "%{$search}%");
                });
            })
            ->groupBy('d.product_id', 'pr.code', 'pr.name')
            ->havingRaw('SUM(GREATEST(COALESCE(d.cantidad_requerida_kg, 0) - COALESCE(d.cantidad_ejecutada_kg, 0), 0)) > 0')
            ->selectRaw('
                d.product_id AS producto_id,
                pr.code AS codigo,
                pr.name AS producto,
                SUM(GREATEST(COALESCE(d.cantidad_requerida_kg, 0) - COALESCE(d.cantidad_ejecutada_kg, 0), 0)) AS necesidad_pendiente_kg
            ');
    }

    private function consultaInventario(?int $sedeId): Builder
    {
        return DB::table('inventories')
            ->when($sedeId, fn (Builder $query) => $query->where('sede_id', $sedeId))
            ->groupBy('producto_id')
            ->selectRaw('
                producto_id,
                SUM(stock) AS stock_fisico_kg,
                SUM(COALESCE(min_stock, 0)) AS stock_seguridad_kg,
                GREATEST(SUM(stock) - SUM(COALESCE(min_stock, 0)), 0) AS stock_utilizable_kg
            ');
    }

    private function consultaProveedor(?int $sedeId): Builder
    {
        $recepciones = DB::table('entregas_proveedor')
            ->groupBy('detalle_id')
            ->selectRaw('detalle_id, SUM(cantidad_entregada) AS total_entregado');

        return DB::table('orden_compra_proveedor_detalles as dp')
            ->join('orden_compra_proveedores as op', 'op.id', '=', 'dp.orden_id')
            ->leftJoinSub($recepciones, 'r', 'r.detalle_id', '=', 'dp.id')
            ->whereIn('op.estado_id', [
                EstadoEnum::PENDIENTE->value,
                EstadoEnum::ENTREGA_PARCIAL->value,
            ])
            ->whereNotNull('dp.producto_id')
            ->when($sedeId, fn (Builder $query) => $query->where('op.sede_id', $sedeId))
            ->groupBy('dp.producto_id')
            ->selectRaw('
                dp.producto_id,
                SUM(
                    GREATEST(
                        dp.cantidad_solicitada - GREATEST(COALESCE(dp.cantidad_entregada, 0), COALESCE(r.total_entregado, 0)),
                        0
                    )
                ) AS proveedor_pendiente_kg
            ');
    }

    private function aplicarFiltroEstado(Builder $query, ?string $estado): void
    {
        $faltante = 'n.necesidad_pendiente_kg - COALESCE(i.stock_utilizable_kg, 0) - COALESCE(p.proveedor_pendiente_kg, 0)';

        match ($estado) {
            'FALTANTE_COMPRA' => $query->whereRaw("{$faltante} > 0"),
            'CUBIERTO_CON_PROVEEDOR' => $query
                ->whereRaw("{$faltante} <= 0")
                ->whereRaw('COALESCE(i.stock_utilizable_kg, 0) < n.necesidad_pendiente_kg')
                ->whereRaw('COALESCE(p.proveedor_pendiente_kg, 0) > 0'),
            'CUBIERTO_CON_STOCK' => $query
                ->whereRaw('COALESCE(i.stock_utilizable_kg, 0) >= n.necesidad_pendiente_kg'),
            default => null,
        };
    }

    private function ordenesClientePorProducto($productoIds, ?int $sedeId)
    {
        return Orden_Compra_Detalle::with(['orden:id,cliente_id,sede_id,fecha_entrega', 'orden.cliente:id,nombre'])
            ->whereIn('product_id', $productoIds)
            ->whereHas('orden', function ($query) use ($sedeId) {
                $query->whereIn('estado_id', [
                    EstadoEnum::PENDIENTE->value,
                    EstadoEnum::ENTREGA_PARCIAL->value,
                ])->when($sedeId, fn ($subquery) => $subquery->where('sede_id', $sedeId));
            })
            ->get()
            ->filter(fn ($detalle) => $this->necesidadPendiente($detalle) > 0)
            ->map(fn ($detalle) => [
                'producto_id' => $detalle->product_id,
                'orden_id' => $detalle->orden_compra_id,
                'detalle_id' => $detalle->id,
                'cliente' => $detalle->orden?->cliente?->nombre ?? 'Sin cliente',
                'fecha_entrega' => $detalle->orden?->fecha_entrega,
                'necesidad_pendiente_kg' => round($this->necesidadPendiente($detalle), 2),
            ])
            ->groupBy('producto_id');
    }

    private function ordenesProveedorPorProducto($productoIds, ?int $sedeId)
    {
        return OrdenCompraProveedorDetalle::with([
            'orden:id,numero_orden,proveedor_id,sede_id,estado_id,fecha_entrega',
            'orden.proveedor:id,nombre',
            'entregas:id,detalle_id,cantidad_entregada',
        ])
            ->whereIn('producto_id', $productoIds)
            ->whereHas('orden', function ($query) use ($sedeId) {
                $query->whereIn('estado_id', [
                    EstadoEnum::PENDIENTE->value,
                    EstadoEnum::ENTREGA_PARCIAL->value,
                ])->when($sedeId, fn ($subquery) => $subquery->where('sede_id', $sedeId));
            })
            ->get()
            ->map(fn ($detalle) => [
                'producto_id' => $detalle->producto_id,
                'orden_id' => $detalle->orden_id,
                'numero_orden' => $detalle->orden?->numero_orden,
                'proveedor' => $detalle->orden?->proveedor?->nombre ?? 'Sin proveedor',
                'fecha_entrega' => $detalle->orden?->fecha_entrega,
                'pendiente_kg' => round($this->cantidadProveedorPendiente($detalle), 2),
            ])
            ->filter(fn ($orden) => $orden['pendiente_kg'] > 0)
            ->groupBy('producto_id');
    }

    private function necesidadPendiente($detalle): float
    {
        return max(0, (float) ($detalle->cantidad_requerida_kg ?? 0) - (float) ($detalle->cantidad_ejecutada_kg ?? 0));
    }

    private function cantidadProveedorPendiente($detalle): float
    {
        $entregado = max(
            (float) ($detalle->cantidad_entregada ?? 0),
            (float) $detalle->entregas->sum('cantidad_entregada')
        );

        return max(0, (float) $detalle->cantidad_solicitada - $entregado);
    }

    private function resolverSede($user, $sedeIdFiltro): ?int
    {
        $puedeVerTodas = in_array((int) $user->role_id, [
            RolEnum::ADMINISTRADOR->value,
            RolEnum::ADMINISTRATIVO->value,
        ]);

        return ($puedeVerTodas && $sedeIdFiltro) ? (int) $sedeIdFiltro : $user->sede_id;
    }
}
