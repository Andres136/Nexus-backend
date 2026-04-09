<?php

namespace App\Services\Crm;

use App\Models\Crm\AlistamientoOt;
use App\Models\Crm\Inventario;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\Orden_servicio\OrdenServicio;
use App\Models\Crm\OrdenCompraProveedor;

use App\Models\Crm\OrdenCompraProveedorDetalle;
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
            // Agrega más datos según sea necesario
        ];
    }
    public function obtenerTrazabilidad($productoId, $filters = []): array
    {
        $producto = product::find($productoId);

        if (!$producto) {
            abort(404, 'Producto no encontrado');
        }

        $stock = $this->obtenerStock($productoId);
        $comprasProveedor = $this->obtenerComprasProveedor($productoId);
        $ordenesTrabajo = $this->obtenerOrdenesTrabajo($productoId);
        $alistamientosOt = $this->obtenerAlistamientosOt($productoId);
        $rutas = $this->obtenerRutas($productoId);

        //  NUEVO: estados por orden
        $estadosPorOrden = $this->calcularEstadosPorOrden($productoId, $filters);
        $ordenesCompletas = $this->construirOrdenesCompletas($productoId, $filters);
        //  NUEVO: estado general del producto

        return [
            'producto' => [
                'id' => $producto->id,
                'nombre' => $producto->nombre ?? $producto->name ?? null,
            ],
            'ordenes' => $ordenesCompletas,
            'stock' => $stock,
            'compras_proveedor' => $comprasProveedor,
            'ordenes_trabajo' => $ordenesTrabajo,
            'alistamientos_ot' => $alistamientosOt,
            'ruta' => $rutas,


        ];
    }
   
private function calcularEstadosPorOrden($productoId, $filters = [], $sedeId = null)
{
    $ordenes = collect();

    // 1. Trae todas las OTs y sus relaciones de una vez
  $ots = OrdenDeTrabajo::with([
        'ordenCompra.cliente',
        'ordenCompra.detalles'
    ])
    ->whereIn('estado_id', [1, 5]) // 👈 AQUÍ
    ->when($productoId, function ($query) use ($productoId) {
        $query->whereHas('ordenCompra.detalles', function ($q) use ($productoId) {
            $q->where('product_id', $productoId);
        });
    })->when($sedeId, function ($query) use ($sedeId) {
    $query->whereHas('ordenCompra', function ($q) use ($sedeId) {
        $q->where('sede_id', $sedeId);
    });
})
    ->get();

    $otIds = $ots->pluck('id')->all();
    $ordenCompraIds = $ots->pluck('orden_compra_id')->all();

    // Alistamientos y Alistamientos OT agrupados por OT
    $alistamientos = Alistamiento::whereIn('orden_trabajo_id', $otIds)
        ->when($productoId, function ($q) use ($productoId) {
            $q->whereHas('ordenTrabajo.ordenCompra.detalles', function ($q2) use ($productoId) {
                $q2->where('product_id', $productoId);
            });
        })
        ->select('orden_trabajo_id', DB::raw('SUM(cantidad) as cantidad'))
        ->groupBy('orden_trabajo_id')
        ->pluck('cantidad', 'orden_trabajo_id');

    $alistamientosOt = AlistamientoOt::whereIn('orden_trabajo_id', $otIds)
        ->when($productoId, function ($q) use ($productoId) {
            $q->where('producto_id', $productoId);
        })
        ->select('orden_trabajo_id', DB::raw('SUM(cantidad) as cantidad'))
        ->groupBy('orden_trabajo_id')
        ->pluck('cantidad', 'orden_trabajo_id');

    // Alistamiento en proceso y listo (existe)
    $alistamientoProceso = Alistamiento::whereIn('orden_trabajo_id', $otIds)
            ->when($productoId, function ($q) use ($productoId) {
                $q->whereHas('ordenTrabajo.ordenCompra.detalles', function ($q2) use ($productoId) {
                    $q2->where('product_id', $productoId);
                });
            })
        ->select('orden_trabajo_id')
        ->get()
        ->pluck('orden_trabajo_id')
        ->unique()
        ->flip();

    $alistamientoListo = AlistamientoOt::whereIn('orden_trabajo_id', $otIds)
        ->when($productoId, function ($q) use ($productoId) {
            $q->where('producto_id', $productoId);
        })
        ->select('orden_trabajo_id')
        ->get()
        ->pluck('orden_trabajo_id')
        ->unique()
        ->flip();

    // Rutas activas
    $rutas = DeliveryEvent::whereIn('orden_id', $ordenCompraIds)
        ->whereIn('estado', ['pendiente', 'en_ruta'])
        ->pluck('orden_id')
        ->unique()
        ->flip();

    // Procesa OTs en memoria
    foreach ($ots as $ot) {
        $ordenCompraId = $ot->orden_compra_id;

        $cantidadTotal = 0;
        foreach ($ot->ordenCompra->detalles as $detalle) {
            if (!$productoId || $detalle->product_id == $productoId) {
                $cantidadTotal += $detalle->cantidad_requerida_kg ?? 0;
            }
        }

        $cantidadAlistada = $alistamientos[$ot->id] ?? 0;
        $cantidadProgramada = $alistamientosOt[$ot->id] ?? 0;
        $enProceso = isset($alistamientoProceso[$ot->id]);
        $listo = isset($alistamientoListo[$ot->id]);
        $enRuta = isset($rutas[$ordenCompraId]);

        $estado = 'EN_PRODUCCION';
        if ($enRuta) {
            $estado = 'EN_RUTA';
        } elseif ($cantidadAlistada >= $cantidadTotal && $cantidadTotal > 0) {
            $estado = 'ALISTAMIENTO_LISTO';
        } elseif ($enProceso || $cantidadAlistada > 0) {
            $estado = 'ALISTAMIENTO_EN_PROCESO';
        } elseif ($listo) {
            $estado = 'ALISTAMIENTO_PROGRAMADO';
        }

        $ordenes->push([
            'orden_compra_id' => $ordenCompraId,
            'orden_trabajo_id' => $ot->id,
            'cliente_id' => $ot->ordenCompra->cliente_id ?? null,
            'cliente' => optional($ot->ordenCompra->cliente)->nombre ?? null,
            'cantidad_pedida' => $cantidadTotal,
            'cantidad_alistada' => $cantidadAlistada,
            'faltante' => max($cantidadTotal - $cantidadAlistada, 0),
            'alerta' => ($cantidadTotal - $cantidadAlistada) > 0 ? 'PENDIENTE' : 'OK',
            'porcentaje_avance' => $cantidadTotal > 0
                ? round(($cantidadAlistada / $cantidadTotal) * 100, 2)
                : 0,
            'atrasado' => $ot->fecha_entrega
                ? now()->gt($ot->fecha_entrega)
                : false,
            'numero_orden' => $ot->ordenCompra->numero ?? null,
            'estado' => $estado,
        ]);
    }

    // 2. Órdenes de compra sin OT
    $ordenesCompra = Orden_Compra::when($productoId, function ($query) use ($productoId) {
        $query->whereHas('detalles', function ($q) use ($productoId) {
            $q->where('product_id', $productoId);
        });
    })->get();

    $ordenesConOT = $ordenes->pluck('orden_compra_id')->all();

    // Trae todos los detalles de proveedor de una vez
    $detallesProveedor = OrdenCompraProveedorDetalle::whereIn('orden_id', $ordenesCompra->pluck('id'))
        ->when($productoId, function ($q) use ($productoId) {
            $q->where('producto_id', $productoId);
        })
        ->get()
        ->groupBy('orden_id');

    foreach ($ordenesCompra as $oc) {
        if (in_array($oc->id, $ordenesConOT)) {
            continue;
        }

        $detalles = $detallesProveedor[$oc->id] ?? collect();
        $cantidadCompra = $detalles->sum('cantidad_solicitada');
        $cantidadRecibida = $detalles->sum('cantidad_entregada');
        $faltanteCompra = max($cantidadCompra - $cantidadRecibida, 0);
        $estadoCompra = 'EN_COMPRA';

        if ($faltanteCompra == 0) {
            $estadoCompra = 'COMPRA_COMPLETADA';
        } elseif ($cantidadRecibida > 0) {
            $estadoCompra = 'COMPRA_PARCIAL';
        }

        $ordenes->push([
            'orden_compra_id' => $oc->id,
            'cantidad_progranada' => 0,
            'numero_orden' => $oc->numero ?? null,
            'cliente_id' => $oc->cliente_id ?? null,
            'cliente' => optional($oc->cliente)->nombre ?? null,
            'cantidad_pedida' => $cantidadCompra,
            'cantidad_recibida' => $cantidadRecibida,
            'faltante' => $faltanteCompra,
            'alerta' => $faltanteCompra > 0 ? 'PENDIENTE' : 'OK',
            'porcentaje_avance' => $cantidadCompra > 0
                ? round(($cantidadRecibida / $cantidadCompra) * 100, 2)
                : 0,
            'estado' => $estadoCompra,
        ]);
    }

    // Filtros finales en memoria
    if (!empty($filters['estado'])) {
        $ordenes = $ordenes->where('estado', $filters['estado']);
    }
    if (!empty($filters['cliente_id'])) {
        $ordenes = $ordenes->where('cliente_id', $filters['cliente_id']);
    }
    if (!empty($filters['pendientes'])) {
        $ordenes = $ordenes->where('faltante', '>', 0);
    }
    if (!empty($filters['atrasados'])) {
        $ordenes = $ordenes->where('atrasado', true);
    }
    if (!empty($filters['avance_min'])) {
        $ordenes = $ordenes->where('porcentaje_avance', '>=', $filters['avance_min']);
    }

    return $ordenes->values();
}

    private function obtenerStock($productoId)
    {
        return Inventario::with('bodega')
            ->where('producto_id', $productoId)
            ->selectRaw('bodega_id, SUM(stock) as stock')
            ->groupBy('bodega_id')
            ->get()
            ->map(function ($item) {
                return [
                    'bodega_id' => $item->bodega_id,
                    'bodega' => $item->bodega->nombre ?? null,
                    'stock' => (float) $item->stock,
                ];
            })
            ->values();
    }
    private function obtenerComprasProveedor($productoId)
    {
        return OrdenCompraProveedorDetalle::with(['orden', 'orden.proveedor'])
           ->when($productoId, function ($q) use ($productoId) {
    $q->where('producto_id', $productoId);
})
            ->whereColumn('cantidad_entregada', '<', 'cantidad_solicitada')
            ->get()
            ->map(function ($item) {
                return [
                    'detalle_id' => $item->id,
                    'orden_id' => $item->orden_id,
                    'proveedor_id' => $item->proveedor_id,
                    'proveedor' => optional($item->orden->proveedor ?? null)->nombre,
                    'cantidad_solicitada' => $item->cantidad_solicitada,
                    'cantidad_entregada' => $item->cantidad_entregada,
                    'pendiente' => max(($item->cantidad_solicitada ?? 0) - ($item->cantidad_entregada ?? 0), 0),
                ];
            })
            ->values();
    }

    private function obtenerOrdenesTrabajo($productoId)
    {
        return OrdenDeTrabajo::with('ordenCompra')
            ->whereHas('ordenCompra.detalles', function ($q) use ($productoId) {
                $q->where('product_id', $productoId);
            })
            ->get()
            ->map(function ($item) {
                return [
                    'orden_trabajo_id' => $item->id,
                    'orden_compra_id' => $item->orden_compra_id,
                    'estado_id' => $item->estado_id,
                    'fecha_entrega' => $item->fecha_entrega,

                ];
            })
            ->values();
    }

private function construirOrdenesCompletas($productoId, $filters = [])
{
    $ordenesBase = $this->calcularEstadosPorOrden($productoId, $filters);

    // IDs de órdenes de compra y trabajo
    $ordenCompraIds = $ordenesBase->pluck('orden_compra_id')->unique()->filter()->values();
    $ordenTrabajoIds = $ordenesBase->pluck('orden_trabajo_id')->unique()->filter()->values();

    // Inventarios por bodega
    $inventarios = Inventario::with('bodega')
        ->when($productoId, function ($q) use ($productoId) {
            $q->where('producto_id', $productoId);
        })
        ->selectRaw('bodega_id, SUM(stock) as stock')
        ->groupBy('bodega_id')
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item->bodega_id => [
                'bodega' => $item->bodega->nombre ?? 'N/A',
                'stock' => (float) $item->stock,
            ]];
        });

    // Compras proveedor por orden
    $comprasProveedor = OrdenCompraProveedorDetalle::with('orden.proveedor')
        ->whereIn('orden_id', $ordenCompraIds)
        ->when($productoId, function ($q) use ($productoId) {
            $q->where('producto_id', $productoId);
        })
        ->get()
        ->groupBy('orden_id');

    // Producción, alistamiento y ruta
    $produccion = OrdenDeTrabajo::whereIn('orden_compra_id', $ordenCompraIds)->pluck('orden_compra_id')->unique()->flip();
    $alistamientos = Alistamiento::whereIn('orden_trabajo_id', $ordenTrabajoIds)->pluck('orden_trabajo_id')->unique()->flip();
    $rutas = DeliveryEvent::whereIn('orden_id', $ordenCompraIds)
        ->whereIn('estado', ['pendiente', 'en_ruta'])
        ->pluck('orden_id')->unique()->flip();

    // Detalles de orden de compra y productos
    $ordenesCompra = Orden_Compra::with('detalles.product')
        ->whereIn('id', $ordenCompraIds)
        ->get()
        ->keyBy('id');

    // Alistamientos por detalle de orden
    $detalleIds = $ordenesCompra->flatMap(function ($oc) {
        return $oc->detalles->pluck('id');
    })->unique()->values();

    $alistamientosPorDetalle = Alistamiento::whereIn('detalle_id', $detalleIds)
        ->select('detalle_id', DB::raw('SUM(cantidad) as cantidad'))
        ->groupBy('detalle_id')
        ->pluck('cantidad', 'detalle_id');

    return $ordenesBase->map(function ($orden) use (
        $productoId, $inventarios, $comprasProveedor, $produccion, $alistamientos, $rutas, $ordenesCompra, $alistamientosPorDetalle
    ) {
        // Bodegas
        $bodegas = $inventarios->values();

        // Compras
        $compras = ($comprasProveedor[$orden['orden_compra_id']] ?? collect())->map(function ($item) {
            return [
                'proveedor' => optional($item->orden->proveedor)->nombre,
                'fecha_oc' => $item->orden->created_at,
                'cantidad_solicitada' => $item->cantidad_solicitada,
                'cantidad_entregada' => $item->cantidad_entregada,
                'pendiente' => max(($item->cantidad_solicitada ?? 0) - ($item->cantidad_entregada ?? 0), 0),
            ];
        })->values();

        // Estados
        $enProduccion = isset($produccion[$orden['orden_compra_id']]);
$ordenTrabajoId = $orden['orden_trabajo_id'] ?? null;

$enAlistamiento = $ordenTrabajoId
    ? isset($alistamientos[$ordenTrabajoId])
    : false;
        $enRuta = isset($rutas[$orden['orden_compra_id']]);

        // Productos detallados
        $productos = [];
        $ordenModel = $ordenesCompra[$orden['orden_compra_id']] ?? null;
        if ($ordenModel && $ordenModel->detalles) {
            $productos = $ordenModel->detalles->map(function ($detalle) use ($alistamientosPorDetalle) {
                $cantidadPedida = $detalle->cantidad_requerida_kg ?? 0;
                $cantidadAlistada = $alistamientosPorDetalle[$detalle->id] ?? 0;
                return [
                    'detalle_id' => $detalle->id,
                    'producto_id' => $detalle->product_id,
                    'nombre' => optional($detalle->product)->name,
                    'cantidad_pedida' => $cantidadPedida,
                    'cantidad_alistada' => $cantidadAlistada,
                    'faltante' => max($cantidadPedida - $cantidadAlistada, 0),
                    'porcentaje' => $cantidadPedida > 0
                        ? round(($cantidadAlistada / $cantidadPedida) * 100, 2)
                        : 0,
                ];
            })->values();
        }

        return [
            // BASE
            'orden_compra_id' => $orden['orden_compra_id'],
            'orden_trabajo_id' => $orden['orden_trabajo_id'] ?? null,
            'cliente_id' => $orden['cliente_id'],
            'cliente' => $orden['cliente'],
            'numero_orden' => $orden['numero_orden'] ?? null,

            // ESTADO
            'estado' => $orden['estado'],
            'alerta' => $orden['alerta'],
            'atrasado' => $orden['atrasado'],

            // CANTIDADES
            'cantidad_pedida' => $orden['cantidad_pedida'],
            'cantidad_alistada' => $orden['cantidad_alistada'],
            'faltante' => $orden['faltante'],
            'porcentaje_avance' => $orden['porcentaje_avance'],

            // INDICADORES
            'stock_disponible' => $bodegas->sum('stock'),
            'bodegas' => $bodegas,
            'compras' => $compras,

            'en_produccion' => $enProduccion,
            'en_alistamiento' => $enAlistamiento,
            'en_ruta' => $enRuta,

            // PRODUCTOS DETALLADOS
            'productos' => $productos,
        ];
    })->values();
}

    public function obtenerTrazabilidadGeneral($filters = [], $sedeId = null, $perPage = 10)
    {
        $ordenesCompletas = $this->construirOrdenesCompletas(null, $filters);

        return [
            'ordenes' => $ordenesCompletas
        ];
    }

    private function obtenerAlistamientosOt($productoId)
    {
        return AlistamientoOt::with(['bodega', 'ordenTrabajo'])
            ->where('producto_id', $productoId)
            ->get()
            ->map(function ($item) {

                $estado = 'PENDIENTE';

                if ($item->ordenTrabajo) {
                    switch ($item->ordenTrabajo->estado_id) {
                        case 1:
                            $estado = 'PENDIENTE';
                            break;
                        case 2:
                            $estado = 'COMPLETADO';
                            break;
                        case 5:
                            $estado = 'ENTREGA_PARCIAL';
                            break;
                    }
                }

                //  lógica adicional por cantidad
                if ($item->cantidad > 0 && $estado === 'PENDIENTE') {
                    $estado = 'EN_PROCESO';
                }

                return [
                    'id' => $item->id,
                    'orden_trabajo_id' => $item->orden_trabajo_id,
                    'bodega' => $item->bodega->nombre ?? null,
                    'cantidad' => $item->cantidad,
                    'estado' => $estado,
                    'estado_id' => $item->ordenTrabajo->estado_id ?? null,
                    'fecha_alistamiento' => $item->fecha_alistamiento,
                ];
            })
            ->values();
    }

    private function obtenerRutas($productoId)
    {
        return DeliveryEvent::with('orden')
            ->whereHas('orden.detalles', function ($q) use ($productoId) {
                $q->where('product_id', $productoId);
            })
            ->whereIn('estado', ['pendiente', 'en_proceso']) // 👈 SOLO ACTIVAS
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'orden_id' => $item->orden_id,
                    'estado' => $item->estado,
                    'fecha_entrega' => $item->fecha_entrega,
                ];
            })
            ->values();
    }
}
