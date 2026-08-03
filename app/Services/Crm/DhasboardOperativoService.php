<?php

namespace App\Services\Crm;

use App\EstadoEnum;
use App\Models\Crm\AlistamientoOt;
use App\Models\Crm\Inventario;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\Orden_servicio\OrdenServicio;
use App\Models\Crm\OrdenCompraProveedor;

use App\Models\Crm\OrdenCompraProveedorDetalle;
use App\Models\Crm\OrdenCompraProveedorDetalleOrigen;
use App\Models\Crm\OrdenComprasHistorial;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Crm\product;
use App\Models\Rutas\DeliveryEvent;
use App\Models\Vsm\Alistamiento;
use Illuminate\Support\Facades\DB;


class DhasboardOperativoService
{
    public function getDatosDashboard()
    {
        // Aquí puedes agregar la lógica para obtener los datos necesarios para el dashboard operativo
        // Por ejemplo, podrías consultar las órdenes de trabajo, tareas, inventarios, etc.
        Orden_Compra::with('sede')->get(); // Ejemplo de consulta para obtener órdenes de compra con su sede relacionada
        OrdenCompraProveedor::with('proveedor')->get(); // Ejemplo de consulta para obtener órdenes de compra a proveedores con su proveedor relacionado
        OrdenServicio::with('proveedor')->get(); // Ejemplo de consulta para obtener órdenes de servicio con su proveedor relacionado
        Inventario::with('producto')->get(); // Ejemplo de consulta para obtener inventarios con su producto relacionado

        OrdenDeTrabajo::with('ordenCompra')->get(); // Ejemplo de consulta para obtener órdenes de trabajo con su orden de compra relacionada
        Alistamiento::with('ordenTrabajo')->get(); // Ejemplo de consulta para obtener alistamientos con su orden de trabajo relacionada
        AlistamientoOt::with('ordenTrabajo')->get(); // Ejemplo de consulta para obtener alistamientos de OT con su orden de trabajo relacionada
        DeliveryEvent::with('ordenTrabajo')->get(); // Ejemplo de consulta para obtener eventos de delivery con su orden de trabajo relacionada

        return [
            'total_ordenes_trabajo' => 10,
            'ordenes_trabajo_pendientes' => 5,
            'ordenes_trabajo_en_proceso' => 3,
            'ordenes_trabajo_completadas' => 2,
           
        ];
    }

public function getPrioridadesActivas($filters = [])
{
    $perPage = max(1, (int)($filters['per_page'] ?? 25));
    $page    = max(1, (int)($filters['page']     ?? 1));

    $base = OrdenCompraProveedorDetalleOrigen::query()
        ->whereHas('ordenCompra', function ($q) {
            $q->whereIn('estado_id', [
                EstadoEnum::PENDIENTE->value,
                EstadoEnum::ENTREGA_PARCIAL->value,
            ]);
        })
        ->where('cantidad_prioridad', '>', 0)
        ->when(!empty($filters['sede_id']), fn($q) => $q->where('sede_id', $filters['sede_id']))
        ->when(!empty($filters['proveedor_id']), function ($q) use ($filters) {
            $q->whereHas('detalleProveedor.orden', function ($oc) use ($filters) {
                $oc->where('proveedor_id', $filters['proveedor_id']);
            });
        })
        ->when(!empty($filters['solo_pendientes']), fn($q) => $q->whereRaw('cantidad_recibida_aplicada < cantidad_prioridad'))
        ->when(!empty($filters['search']), function ($q) use ($filters) {
            $q->whereHas('producto', function ($pq) use ($filters) {
                $pq->where('name', 'LIKE', "%{$filters['search']}%")
                   ->orWhere('code', 'LIKE', "%{$filters['search']}%");
            });
        });

    $stats = (clone $base)->selectRaw('
        COUNT(*) as total,
        SUM(CASE WHEN cantidad_recibida_aplicada >= cantidad_prioridad THEN 1 ELSE 0 END) as completas,
        SUM(CASE WHEN cantidad_recibida_aplicada < cantidad_prioridad THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN cantidad_prioridad > cantidad_recibida_aplicada THEN cantidad_prioridad - cantidad_recibida_aplicada ELSE 0 END) as kg_pendiente
    ')->first();

    $paginador = (clone $base)->with([
        'detalleProveedor.orden.proveedor',
        'ordenCompra.cliente',
        'ordenCompra.sede',
        'producto',
    ])->paginate($perPage, ['*'], 'page', $page);

    $origenes = collect($paginador->items());

    $ocIds = $origenes->pluck('orden_compra_id')->unique();
    $ordenesTrabajo = DB::table('orden_de_trabajos')
        ->whereIn('orden_compra_id', $ocIds)
        ->get()
        ->keyBy('orden_compra_id');

    $data = $origenes->map(function ($origen) use ($ordenesTrabajo) {
        $oc        = $origen->ordenCompra;
        $ot        = $ordenesTrabajo[$oc->id] ?? null;
        $ocProv    = $origen->detalleProveedor?->orden;
        $pendiente = max($origen->cantidad_prioridad - $origen->cantidad_recibida_aplicada, 0);

        return [
            'origen_id'          => $origen->id,
            'oc_id'              => $oc->id,
            'oc_numero'          => $oc->numero,
            'oc_fecha_entrega'   => $oc->fecha_entrega,
            'cliente'            => optional($oc->cliente)->nombre,
            'sede'               => optional($oc->sede)->nombre,
            'orden_trabajo_id'   => $ot?->id,
            'codigo_producto'    => $origen->producto?->code,
            'producto'           => $origen->producto?->name,
            'cantidad_prioridad' => $origen->cantidad_prioridad,
            'cantidad_recibida'  => $origen->cantidad_recibida_aplicada,
            'pendiente'          => $pendiente,
            'pct_cumplimiento'   => $origen->cantidad_prioridad > 0
                ? round(($origen->cantidad_recibida_aplicada / $origen->cantidad_prioridad) * 100, 1)
                : 0,
            'completa'            => $origen->cantidad_recibida_aplicada >= $origen->cantidad_prioridad,
            'oc_proveedor_id'     => $ocProv?->id,
            'oc_proveedor_numero' => $ocProv?->numero_orden,
            'proveedor'           => $ocProv?->proveedor?->nombre,
        ];
    })->values();

    return [
        'data'         => $data,
        'current_page' => $paginador->currentPage(),
        'last_page'    => $paginador->lastPage(),
        'per_page'     => $paginador->perPage(),
        'total'        => $paginador->total(),
        'stats'        => [
            'total'        => (int)   ($stats->total        ?? 0),
            'completas'    => (int)   ($stats->completas    ?? 0),
            'pendientes'   => (int)   ($stats->pendientes   ?? 0),
            'kg_pendiente' => (float) ($stats->kg_pendiente ?? 0),
        ],
    ];
}

public function obtenerOrdenesCompraVSM($filters = [])
{
$sedeId = $filters['sede_id'] ?? null;



    // 🔹 1. CARGA BASE CON RELACIONES (Evitamos N+1)
$ordenes = Orden_Compra::with([
        'cliente',
        'detalles.product',
        'sede'
    ])
    ->whereIn('estado_id', [
        EstadoEnum::PENDIENTE->value,
        EstadoEnum::ENTREGA_PARCIAL->value,
    ])
    ->whereNot('estado_id', EstadoEnum::INACTIVO->value)

    ->when(!empty($filters['producto_id']), function ($query) use ($filters) {
        $query->whereHas('detalles', function ($q) use ($filters) {
            $q->where('product_id', $filters['producto_id']);
        });
    })

->when(!empty($filters['sede_id']), function ($q) use ($filters) {
    $q->where(function ($sub) use ($filters) {
        $sub->where('sede_id', $filters['sede_id'])
            ->orWhereNull('sede_id'); //  incluye las que no tienen sede
    });
})

  
    ->when(!empty($filters['cliente']), function ($query) use ($filters) {
        $query->where('cliente_id', $filters['cliente']);
    })
    ->when(!empty($filters['search']), function ($query) use ($filters) {
    $query->where(function ($sub) use ($filters) {
        $sub->whereHas('ordenTrabajo', function ($q) use ($filters) {
                $q->where('id', 'LIKE', "%{$filters['search']}%");
            })
            ->orWhere('orden_compra_cliente', 'LIKE', "%{$filters['search']}%");
    });
})

    ->get();   

    
    $ordenIds = $ordenes->pluck('id');
    $detalleIds = $ordenes->flatMap(fn($oc) => $oc->detalles->pluck('id'))->unique();


$historial = OrdenComprasHistorial::whereIn('orden_compra_id', $ordenIds)
    ->orderBy('created_at', 'desc')
    ->get()
    ->groupBy('orden_compra_id');

    // 🔹 2. RECOPILACIÓN DE DATOS EXTERNOS (Bulk Queries)
    
    // Compras con trazabilidad exacta. Las ordenes antiguas/manuales quedan cubiertas por fallback.
    $comprasExactasPorOc = OrdenCompraProveedorDetalleOrigen::with([
            'detalleProveedor.orden.proveedor',
        ])
        ->whereIn('orden_compra_id', $ordenIds)
        ->when(!empty($filters['producto_id']), function ($q) use ($filters) {
            $q->where('producto_id', $filters['producto_id']);
        })
        ->get()
        ->groupBy('orden_compra_id');

    $productoIds = $ordenes->flatMap(fn($oc) => $oc->detalles->pluck('product_id'))->unique()->filter()->values();

    $proveedorDetallesFallback = OrdenCompraProveedorDetalle::with(['orden.proveedor'])
        ->whereIn('producto_id', $productoIds)
        ->whereRaw('COALESCE(cantidad_entregada, 0) < cantidad_solicitada')
        ->whereHas('orden', function ($query) use ($filters) {
            $query->whereIn('estado_id', [
                EstadoEnum::PENDIENTE->value,
                EstadoEnum::ENTREGA_PARCIAL->value,
            ])
                ->when(!empty($filters['sede_id']), fn($sede) => $sede->where('sede_id', $filters['sede_id']))
                ->when(!empty($filters['bodega_id']), fn($bodega) => $bodega->where('bodega_id', $filters['bodega_id']));
        })
        ->get()
        ->groupBy(fn($detalle) => $detalle->producto_id.'|'.($detalle->orden?->sede_id ?? 'null'));

    // Inventario
    $inventario = Inventario::whereIn('producto_id', $productoIds)
        ->when($sedeId, fn($query) => $query->where('sede_id', $sedeId))
        ->select('producto_id', 'sede_id', DB::raw('SUM(stock) as stock_total'))
        ->groupBy('producto_id', 'sede_id')->get();

    $inventarioPorProductoSede = $inventario->keyBy(fn($item) => $item->producto_id.'|'.($item->sede_id ?? 'null'));
    $inventarioPorProducto = $inventario
        ->groupBy('producto_id')
        ->map(fn($items) => (object) ['stock_total' => $items->sum('stock_total')]);

    // Alistamientos de bodega (Orden de Trabajo): incluye tanto lo alistado
    // con el producto original como con un equivalente/homólogo — ambos
    // cuentan para "lo alistado" del detalle, igual que en TablaDetallesOrden.jsx.
    $alistamientosOtPorDetalle = AlistamientoOt::whereIn('orden_compra_detalle_id', $detalleIds)
        ->get()
        ->groupBy('orden_compra_detalle_id');

    $equivalentes = $alistamientosOtPorDetalle
        ->map(fn($items) => $items->where('tipo', 'equivalente'))
        ->filter(fn($items) => $items->isNotEmpty());

    // Flujo de Trabajo (OT)
    $ordenesTrabajo = DB::table('orden_de_trabajos')->whereIn('orden_compra_id', $ordenIds)->get()->keyBy('orden_compra_id');

    // Despachos
    $despachos = DB::table('delivery_events')
        ->whereIn('orden_id', $ordenIds)
        ->where(fn($q) => $q->where('estado', '!=', 'completado')->orWhere('updated_at', '>=', now()->subDays(2)))
        ->get()->groupBy('orden_id');

    // 🔹 3. PROCESAMIENTO DE LA COLECCIÓN
    return $ordenes->map(function ($oc) use ($filters,
     $comprasExactasPorOc,
      $proveedorDetallesFallback,
      $inventarioPorProductoSede,
      $inventarioPorProducto,
       $equivalentes, $alistamientosOtPorDetalle, $ordenesTrabajo, $despachos, $historial
    ) {
        
        $detalles = !empty($filters['producto_id']) 
            ? $oc->detalles->where('product_id', $filters['producto_id']) 
            : $oc->detalles;

        $totalRequerido = $detalles->sum('cantidad_requerida_kg');

        // --- LÓGICA DE COMPRA ---
        $origenesCompra = $comprasExactasPorOc[$oc->id] ?? collect();
        $usaTrazabilidadExacta = $origenesCompra->isNotEmpty();

        if ($usaTrazabilidadExacta) {
            $totalSolicitado = $origenesCompra->sum(fn($origen) => $origen->cantidad_prioridad ?: $origen->cantidad_solicitada);
            $totalRecibido = $origenesCompra->sum('cantidad_recibida_aplicada');
        } else {
            $detallesProveedorEstimados = $detalles
                ->flatMap(function ($d) use ($proveedorDetallesFallback, $oc) {
                    return $proveedorDetallesFallback[$d->product_id.'|'.($oc->sede_id ?? 'null')] ?? collect();
                })
                ->unique('id')
                ->values();

            $totalSolicitado = $detallesProveedorEstimados->sum('cantidad_solicitada');
            $totalRecibido = $detallesProveedorEstimados->sum('cantidad_entregada');
        }
        $faltanteCompra = max($totalSolicitado - $totalRecibido, 0);

        $estadoCompra = match(true) {
            ($faltanteCompra == 0 && $totalSolicitado > 0) => 'COMPRA_COMPLETA',
            ($totalRecibido > 0) => 'COMPRA_PARCIAL',
            ($totalSolicitado > 0) => 'PENDIENTE_COMPRA',
            default => 'SIN_COMPRA'
        };
//Historial de fechas de ordenes de compra
        $historialOrden = $historial[$oc->id] ?? collect();
        // --- LÓGICA DE ALISTAMIENTO ---
        // Fuente unificada con "Orden de Trabajo": lo alistado es la suma de
        // alistamientos_ot.cantidad por detalle (original + equivalente),
        // igual que TablaDetallesOrden.jsx — ya no se usa alistamiento_detalles
        // (sistema de productividad VSM, ajeno a los equivalentes de bodega).
        $ot = $ordenesTrabajo[$oc->id] ?? null;

        $totalProg = $totalRequerido;
        $totalAlis = $detalles->sum(
            fn($d) => ($alistamientosOtPorDetalle[$d->id] ?? collect())->sum('cantidad')
        );
        $totalFalt = max($totalProg - $totalAlis, 0);

        $estadoAlistamiento = match(true) {
            ($totalAlis <= 0) => 'NO_INICIADO',
            ($totalFalt <= 0 && $totalProg > 0) => 'ALISTADO',
            default => 'EN_ALISTAMIENTO'
        };

        // --- LÓGICA DE DESPACHO ---
        $despachoOrden = $despachos[$oc->id] ?? collect();
        $tieneDespacho = $despachoOrden->isNotEmpty();
        $estadoDespacho = 'NO_DESPACHADO';

        if ($tieneDespacho) {
            $todoCompletado = $despachoOrden->every(fn($d) => $d->estado === 'completado');
            $tienePendiente = $despachoOrden->contains(fn($d) => $d->estado === 'pendiente');
            $estadoDespacho = $todoCompletado ? 'ENTREGADO' : ($tienePendiente ? 'EN_RUTA' : 'NO_DESPACHADO');
        }

        // --- LÓGICA DE INVENTARIO Y PRODUCTOS ---
        $stockDisponible = 0;
        $productosData = $detalles->map(function ($d) use (
            $oc,
            $origenesCompra,
            $usaTrazabilidadExacta,
            $proveedorDetallesFallback,
            $inventarioPorProductoSede,
            $inventarioPorProducto,
            $equivalentes,
            &$stockDisponible
        ) {
            $stockKey = $d->product_id.'|'.($oc->sede_id ?? 'null');
            $stock = $inventarioPorProductoSede[$stockKey]->stock_total
                ?? $inventarioPorProducto[$d->product_id]->stock_total
                ?? 0;
            $stockDisponible += $stock;

            if ($usaTrazabilidadExacta) {
                $origenesProducto = $origenesCompra
                    ->where('orden_compra_detalle_id', $d->id)
                    ->values();
                $compraSolicitada = $origenesProducto->sum(fn($origen) => $origen->cantidad_prioridad ?: $origen->cantidad_solicitada);
                $compraRecibida = $origenesProducto->sum('cantidad_recibida_aplicada');
                $prioridades = $origenesProducto->map(fn($origen) => [
                    'origen_id' => $origen->id,
                    'orden_compra_id' => $origen->orden_compra_id,
                    'orden_compra_detalle_id' => $origen->orden_compra_detalle_id,
                    'cantidad_prioridad' => $origen->cantidad_prioridad ?: $origen->cantidad_solicitada,
                    'cantidad_recibida' => $origen->cantidad_recibida_aplicada,
                    'pendiente' => max(
                        ($origen->cantidad_prioridad ?: $origen->cantidad_solicitada)
                        - $origen->cantidad_recibida_aplicada,
                        0
                    ),
                    'completa' => $origen->cantidad_recibida_aplicada >= ($origen->cantidad_prioridad ?: $origen->cantidad_solicitada),
                    'snapshot' => $origen->prioridad_snapshot,
                    // A qué OC proveedor específica quedó esta prioridad — necesario
                    // para verificar cuál OC recibió cuánto cuando el mismo producto
                    // se anexó a varias OC proveedor distintas.
                    'oc_proveedor_numero' => $origen->detalleProveedor?->orden?->numero_orden,
                    'oc_proveedor_id' => $origen->detalleProveedor?->orden?->id,
                ])->values();
                $ordenesProveedor = $origenesProducto
                    ->map(fn($origen) => $origen->detalleProveedor)
                    ->filter()
                    ->unique('id')
                    ->values();
            } else {
                $detallesProveedor = ($proveedorDetallesFallback[$d->product_id.'|'.($oc->sede_id ?? 'null')] ?? collect())
                    ->unique('id')
                    ->values();
                $compraSolicitada = $detallesProveedor->sum('cantidad_solicitada');
                $compraRecibida = $detallesProveedor->sum('cantidad_entregada');
                $prioridades = collect();
                $ordenesProveedor = $detallesProveedor
                    ->filter()
                    ->unique('id')
                    ->values();
            }

                $requerido = $d->cantidad_requerida_kg ?? 0;
    $entregado = $d->cantidad_enviada ?? 0;
    $faltante = $d->faltantes ?? max($requerido - $entregado, 0);
            $tieneEquivalente = isset($equivalentes[$d->id]);

            $estadoItem = match(true) {
        ($entregado >= $requerido) => 'ENTREGADO',
        ($entregado > 0) => 'PARCIAL',
        ($stock >= $requerido) => 'OK',
        ($tieneEquivalente) => 'HOMOLOGABLE',
        default => 'SIN_STOCK'
    };

            return [
                'detalle_id' => $d->id,
                'producto_id' => $d->product_id,
                'codigo' => $d->product?->code,
                'producto' => optional($d->product)->name,
                
                'requerido' => $d->cantidad_requerida_kg,
                  'entregado' => $entregado, // 
                   'faltante' => $faltante,   // 
                'stock' => $stock,
                'estado' => $estadoItem,
                'tiene_equivalente' => $tieneEquivalente,
                'observaciones' => $d->observaciones,
                'compra_proveedor' => [
                    'trazabilidad' => $usaTrazabilidadExacta ? 'exacta' : 'estimada',
                    'total_solicitado' => $compraSolicitada,
                    'total_recibido' => $compraRecibida,
                    'pendiente' => max($compraSolicitada - $compraRecibida, 0),
                    'prioridades' => $prioridades,
                    'prioridad_completa' => $prioridades->isNotEmpty()
                        ? $prioridades->every(fn($prioridad) => $prioridad['completa'])
                        : null,
                    'ordenes' => $ordenesProveedor->map(fn($detalleProveedor) => [
                        'id' => $detalleProveedor->orden?->id,
                        'detalle_id' => $detalleProveedor->id,
                        'numero_orden' => $detalleProveedor->orden?->numero_orden,
                        'proveedor' => $detalleProveedor->orden?->proveedor?->nombre,
                        'cantidad_solicitada' => $detalleProveedor->cantidad_solicitada,
                        'cantidad_entregada' => $detalleProveedor->cantidad_entregada,
                        'pendiente' => max(
                            (float) $detalleProveedor->cantidad_solicitada
                            - (float) $detalleProveedor->cantidad_entregada,
                            0
                        ),
                    ])->values(),
                ],
            ];
        });

        $estadoInventario = match(true) {
            ($stockDisponible <= 0) => 'SIN_STOCK',
            ($stockDisponible < $totalRequerido) => 'STOCK_PARCIAL',
            default => 'STOCK_OK'
        };

        // --- DETERMINACIÓN DEL ESTADO VSM (JERARQUÍA OPERATIVA) ---
        $tieneSinStock = $productosData->contains('estado', 'SIN_STOCK');
        $tieneHomologable = $productosData->contains('estado', 'HOMOLOGABLE');

        $estadoVSM = match(true) {
            ($estadoDespacho === 'ENTREGADO') => 'ENTREGADO',
            ($estadoDespacho === 'EN_RUTA') => 'EN_RUTA',
            ($estadoAlistamiento === 'ALISTADO') => 'LISTO_PARA_DESPACHO',
            ($estadoAlistamiento === 'EN_ALISTAMIENTO') => 'EN_ALISTAMIENTO',
            (!$tieneSinStock && $estadoInventario === 'STOCK_OK') => 'LISTO_PARA_ALISTAR',
            ($tieneHomologable) => 'REQUIERE_HOMOLOGACION',
            ($estadoInventario === 'STOCK_PARCIAL') => 'STOCK_INSUFICIENTE',
            ($estadoCompra === 'PENDIENTE_COMPRA') => 'ESPERANDO_PROVEEDOR',
            default => 'SIN_STOCK'
        };

        // Filtro de salida: si ya se recibió todo, no es necesario en el VSM activo
       // if ($totalRecibido >= $totalRequerido && $totalRequerido > 0) return null;

        return [
            'orden_id'        => $oc->id,
            'sede_id' => $oc->sede_id,
'sede' => optional($oc->sede)->nombre,
            'orden_trabajo_id'   => $ot->id ?? null,
            'revisada' => $ot ? ($ot->revisada ==1 ? true : false) : null,
            'revisada_at' => $ot->revisada_at ?? null,
            'numero'          => $oc->numero,
            'fecha_entrega'     => $oc->fecha_entrega,
            'cliente'         => optional($oc->cliente)->nombre,
            'estado_id'       => $oc->estado_id,
            'estado'          => $oc->estado_id == 1 ? 'PENDIENTE' : ($oc->estado_id == 5 ? 'ENTREGA_PARCIAL' : 'OTRO'),
            'estado_vsm'      => $estadoVSM,
            'total_requerido' => $totalRequerido,
            'total_recibido'  => $totalRecibido,
            'faltante'        => max($totalRequerido - $totalRecibido, 0),
            'inventario'      => [
                'stock_disponible' => $stockDisponible,
                'estado'           => $estadoInventario
            ],
            'compra'          => [
                'total_solicitado' => $totalSolicitado,
                'total_recibido'   => $totalRecibido,
                'faltante'         => $faltanteCompra,
                'estado'           => $estadoCompra,
                'trazabilidad'     => $usaTrazabilidadExacta ? 'exacta' : 'estimada',
            ],
            'alistamiento'    => [
                'estado'           => $estadoAlistamiento,
                'total_programado' => $totalProg,
                'total_alistado'   => $totalAlis,
                'faltante'         => $totalFalt
            ],
            'productos'       => $productosData,
            'despacho'        => [
                'estado'         => $estadoDespacho,
                'tiene_despacho' => $tieneDespacho
            ],
            'historial' => $historialOrden->map(fn($h) => [
                'fecha_cambio' => $h->created_at,
                'fecha_anterior' => $h->fecha_anterior,
                'fecha_nueva' => $h->fecha_nueva,
                'observacion' => $h->observacion
            ]),
        ];
    })->filter()
->when(!empty($filters['estado_vsm']), function ($collection) use ($filters) {
    return $collection->filter(function ($item) use ($filters) {
        return $item['estado_vsm'] === $filters['estado_vsm'];
    });
})

->when(isset($filters['revisada']), function ($collection) use ($filters) {
    $valor = filter_var($filters['revisada'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $collection->filter(function ($item) use ($valor) {
        return $item['revisada'] === $valor;
    });
})


->values();

    
}
}
