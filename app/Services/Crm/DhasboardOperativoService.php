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
    private function calcularEstadosPorOrden($productoId, $filters = [])
    {
        $ordenes = collect();

        // 1. DESDE ORDENES DE TRABAJO
        $ots = OrdenDeTrabajo::with([
            'ordenCompra.cliente',
            'ordenCompra.detalles'
        ])
            ->when($productoId, function ($query) use ($productoId) {
                $query->whereHas('ordenCompra.detalles', function ($q) use ($productoId) {
                    $q->where('product_id', $productoId);
                });
            })
            ->get();

        foreach ($ots as $ot) {

            $ordenCompraId = $ot->orden_compra_id;

            $cantidadTotal = 0;

            foreach ($ot->ordenCompra->detalles as $detalle) {

                if (!$productoId || $detalle->product_id == $productoId) {
                    $cantidadTotal += $detalle->cantidad_requerida_kg ?? 0;
                }
            }

            $cantidadAlistada = Alistamiento::where('orden_trabajo_id', $ot->id)
    ->when($productoId, function ($q) use ($productoId) {
        $q->where('producto_id', $productoId);
    })
    ->sum('cantidad');
$cantidadProgramada = AlistamientoOt::where('orden_trabajo_id', $ot->id)
    ->when($productoId, function ($q) use ($productoId) {
        $q->where('producto_id', $productoId);
    })
    ->sum('cantidad');
            $alistamientoProceso = Alistamiento::where('orden_trabajo_id', $ot->id)
                ->when($productoId, function ($q) use ($productoId) {
                    $q->where('producto_id', $productoId);
                })
                ->exists();

            //  2. ALISTAMIENTO LISTO (ya preparado)
            $alistamientoListo = AlistamientoOt::where('orden_trabajo_id', $ot->id)
                ->when($productoId, function ($q) use ($productoId) {
                    $q->where('producto_id', $productoId);
                })
                ->exists();

            $ruta = DeliveryEvent::where('orden_id', $ordenCompraId)
                ->whereIn('estado', ['pendiente', 'en_ruta'])
                ->exists();

        $estado = 'EN_PRODUCCION';

if ($ruta) {
    $estado = 'EN_RUTA';

} elseif ($cantidadAlistada >= $cantidadTotal && $cantidadTotal > 0) {
    //  YA COMPLETÓ LO PROGRAMADO
    $estado = 'ALISTAMIENTO_LISTO';

} elseif ($alistamientoProceso || $cantidadAlistada > 0) {
    // PERSONAL TRABAJANDO
    $estado = 'ALISTAMIENTO_EN_PROCESO';

} elseif ($alistamientoListo) {
    //  SOLO PROGRAMADO (NO HAN EMPEZADO)
    $estado = 'ALISTAMIENTO_PROGRAMADO';
}
            $ordenes->push([
                'orden_compra_id' => $ordenCompraId,
                'orden_trabajo_id' => $ot->id,

                //  CLIENTE
                'cliente_id' => $ot->ordenCompra->cliente_id ?? null,
                'cliente' => optional($ot->ordenCompra->cliente)->nombre ?? null,

                //  NUEVO
                'cantidad_pedida' => $cantidadTotal,
                'cantidad_alistada' => $cantidadAlistada,
                'faltante' => max($cantidadTotal - $cantidadAlistada, 0),
                'alerta' => ($cantidadTotal - $cantidadAlistada) > 0 ? 'PENDIENTE' : 'OK',


                //  EXTRA PRO
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

        //  2. ORDENES SIN OT (EN COMPRA)
        $ordenesCompra = Orden_Compra::when($productoId, function ($query) use ($productoId) {
            $query->whereHas('detalles', function ($q) use ($productoId) {
                $q->where('product_id', $productoId);
            });
        })->get();

        foreach ($ordenesCompra as $oc) {

            $yaExiste = $ordenes->where('orden_compra_id', $oc->id)->count();

            if (!$yaExiste) {


                $cantidadCompra = OrdenCompraProveedorDetalle::where('producto_id', $productoId)
                    ->where('orden_id', $oc->id)
                    ->sum('cantidad_solicitada');

                $cantidadRecibida = OrdenCompraProveedorDetalle::where('producto_id', $productoId)
                    ->where('orden_id', $oc->id)
                    ->sum('cantidad_entregada');

                $faltanteCompra = max($cantidadCompra - $cantidadRecibida, 0);
                $estadoCompra = 'EN_COMPRA';

                if ($faltanteCompra == 0) {
                    $estadoCompra = 'COMPRA_COMPLETADA';
                } elseif ($cantidadRecibida > 0) {
                    $estadoCompra = 'COMPRA_PARCIAL';
                }

                $ordenes->push([
                    'orden_compra_id' => $oc->id,
                    'cantidad_progranada' =>$cantidadProgramada ?? 0,

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
        }
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

        return $ordenesBase->map(function ($orden) use ($productoId) {

            // STOCK TOTAL DEL PRODUCTO
        $bodegas = Inventario::with('bodega')
    ->when($productoId, function ($q) use ($productoId) {
        $q->where('producto_id', $productoId);
    })
    ->selectRaw('bodega_id, SUM(stock) as stock')
    ->groupBy('bodega_id')
    ->get()
    ->map(function ($item) {
        return [
            'bodega' => $item->bodega->nombre ?? 'N/A',
            'stock' => (float) $item->stock,
        ];
    });

            //  COMPRAS PENDIENTES POR ORDEN
           $compras = OrdenCompraProveedorDetalle::with('orden.proveedor')
    ->where('orden_id', $orden['orden_compra_id'])
    ->when($productoId, function ($q) use ($productoId) {
        $q->where('producto_id', $productoId);
    })
    ->get()
    ->map(function ($item) {
        return [
            'proveedor' => optional($item->orden->proveedor)->nombre,
            'fecha_oc' => $item->orden->created_at,
            'cantidad_solicitada' => $item->cantidad_solicitada,
            'cantidad_entregada' => $item->cantidad_entregada,
            'pendiente' => max(
                ($item->cantidad_solicitada ?? 0) - ($item->cantidad_entregada ?? 0),
                0
            ),
        ];
    })
    ->values();

            //  PRODUCCIÓN
            $enProduccion = OrdenDeTrabajo::where('orden_compra_id', $orden['orden_compra_id'])->exists();

            //  ALISTAMIENTO (proceso)
            $enAlistamiento = Alistamiento::where('orden_trabajo_id', $orden['orden_trabajo_id'])->exists();

            // RUTA
            $enRuta = DeliveryEvent::where('orden_id', $orden['orden_compra_id'])
                ->whereIn('estado', ['pendiente', 'en_ruta'])
                ->exists();

            // TRAER DETALLES DE LA ORDEN (PRODUCTOS)
            $ordenModel = Orden_Compra::with('detalles.product')
                ->find($orden['orden_compra_id']);

            $productos = [];

            if ($ordenModel && $ordenModel->detalles) {
                $productos = collect($ordenModel->detalles)->map(function ($detalle) {

                    $cantidadPedida = $detalle->cantidad_requerida_kg ?? 0;

                   $cantidadAlistada = Alistamiento::where('orden_trabajo_id', $detalle->id)
    ->sum('cantidad');

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
                //  BASE
                'orden_compra_id' => $orden['orden_compra_id'],
                'orden_trabajo_id' => $orden['orden_trabajo_id'],
                'cliente_id' => $orden['cliente_id'],
                'cliente' => $orden['cliente'],
                'numero_orden' => $orden['numero_orden'] ?? null,

                //  ESTADO
                'estado' => $orden['estado'],
                'alerta' => $orden['alerta'],
                'atrasado' => $orden['atrasado'],

                //  CANTIDADES
                'cantidad_pedida' => $orden['cantidad_pedida'],
                'cantidad_alistada' => $orden['cantidad_alistada'],
                'faltante' => $orden['faltante'],
                'porcentaje_avance' => $orden['porcentaje_avance'],

                //  INDICADORES
                'stock_disponible' => $bodegas->sum('stock'),
'bodegas' => $bodegas,
                'compras' => $compras,

                'en_produccion' => $enProduccion,
                'en_alistamiento' => $enAlistamiento,
                'en_ruta' => $enRuta,

                //  PRODUCTOS DETALLADOS
                'productos' => $productos,
            ];
        })->values();
    }

    public function obtenerTrazabilidadGeneral($filters = [])
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
