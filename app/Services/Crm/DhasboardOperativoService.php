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

public function obtenerOrdenesCompraVSM($filters = [])
{
$sedeId = $filters['sede_id'] ?? null;

    // 🔹 1. CARGA BASE CON RELACIONES (Evitamos N+1)
$ordenes = Orden_Compra::with([
        'cliente',
        'detalles.product',
        'sede'
    ])
    ->whereIn('estado_id', [1, 5])

    ->when(!empty($filters['producto_id']), function ($query) use ($filters) {
        $query->whereHas('detalles', function ($q) use ($filters) {
            $q->where('product_id', $filters['producto_id']);
        });
    })

->when(isset($filters['sede_id']), function ($q) use ($filters) {
    $q->where(function ($sub) use ($filters) {
        $sub->where('sede_id', $filters['sede_id'])
            ->orWhereNull('sede_id'); // 🔥 incluye las que no tienen sede
    });
})

    // 🔥 AQUÍ VA EL CLIENTE
    ->when(!empty($filters['cliente']), function ($query) use ($filters) {
        $query->where('cliente_id', $filters['cliente']);
    })

    ->get(); // 🔥 SIEMPRE AL FINAL     

    
    $ordenIds = $ordenes->pluck('id');
    $detalleIds = $ordenes->flatMap(fn($oc) => $oc->detalles->pluck('id'))->unique();

    // 🔹 2. RECOPILACIÓN DE DATOS EXTERNOS (Bulk Queries)
    
    // Compras
    $compras = OrdenCompraProveedorDetalle::whereIn('orden_id', $ordenIds)
        ->when(!empty($filters['producto_id']), function ($q) use ($filters) {
            $q->where('producto_id', $filters['producto_id']);
        })
        ->select('orden_id', DB::raw('SUM(cantidad_solicitada) as total_solicitado'), DB::raw('SUM(cantidad_entregada) as total_recibido'))
        ->groupBy('orden_id')->get()->keyBy('orden_id');

    // Inventario
    $productoIds = $ordenes->flatMap(fn($oc) => $oc->detalles->pluck('product_id'))->unique();
    $inventario = Inventario::whereIn('producto_id', $productoIds)
        ->where('sede_id', $sedeId)
        ->select('producto_id', DB::raw('SUM(stock) as stock_total'))
        ->groupBy('producto_id')->get()->keyBy('producto_id');

    // Equivalentes
    $equivalentes = AlistamientoOt::whereIn('orden_compra_detalle_id', $detalleIds)
        ->where('tipo', 'equivalente')->get()->groupBy('orden_compra_detalle_id');

    // Flujo de Trabajo (OT -> Alistamiento -> Detalles)
    $ordenesTrabajo = DB::table('orden_de_trabajos')->whereIn('orden_compra_id', $ordenIds)->get()->keyBy('orden_compra_id');
    $alistamientos = DB::table('alistamiento')->whereIn('orden_trabajo_id', $ordenesTrabajo->pluck('id'))->get()->keyBy('orden_trabajo_id');
    $alistamientoDetalles = DB::table('alistamiento_detalles')->whereIn('alistamiento_id', $alistamientos->pluck('id'))->get()->groupBy('alistamiento_id');

    // Despachos
    $despachos = DB::table('delivery_events')
        ->whereIn('orden_id', $ordenIds)
        ->where(fn($q) => $q->where('estado', '!=', 'completado')->orWhere('updated_at', '>=', now()->subDays(2)))
        ->get()->groupBy('orden_id');

    // 🔹 3. PROCESAMIENTO DE LA COLECCIÓN
    return $ordenes->map(function ($oc) use ($filters,
     $compras,
      $inventario,
       $equivalentes, $ordenesTrabajo, $alistamientos, $alistamientoDetalles, $despachos
    ) {
        
        $detalles = !empty($filters['producto_id']) 
            ? $oc->detalles->where('product_id', $filters['producto_id']) 
            : $oc->detalles;

        $totalRequerido = $detalles->sum('cantidad_requerida_kg');

        // --- LÓGICA DE COMPRA ---
        $compra = $compras[$oc->id] ?? null;
        $totalSolicitado = $compra->total_solicitado ?? 0;
        $totalRecibido = $compra->total_recibido ?? 0;
        $faltanteCompra = max($totalSolicitado - $totalRecibido, 0);

        $estadoCompra = match(true) {
            ($faltanteCompra == 0 && $totalSolicitado > 0) => 'COMPRA_COMPLETA',
            ($totalRecibido > 0) => 'COMPRA_PARCIAL',
            ($totalSolicitado > 0) => 'PENDIENTE_COMPRA',
            default => 'SIN_COMPRA'
        };

        // --- LÓGICA DE ALISTAMIENTO ---
        $ot = $ordenesTrabajo[$oc->id] ?? null;
        $alistamiento = $ot ? ($alistamientos[$ot->id] ?? null) : null;
        $detAlist = $alistamiento ? ($alistamientoDetalles[$alistamiento->id] ?? collect()) : collect();

        $totalProg = $detAlist->sum('cantidad_programada');
        $totalAlis = $detAlist->sum('cantidad_alistada');
        $totalFalt = $detAlist->sum('cantidad_faltante');

        $estadoAlistamiento = match(true) {
            (!$alistamiento) => 'NO_INICIADO',
            ($totalFalt == 0 && $totalProg > 0) => 'ALISTADO',
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
        $productosData = $detalles->map(function ($d) use ($inventario, $equivalentes, &$stockDisponible) {
            $stock = $inventario[$d->product_id]->stock_total ?? 0;
            $stockDisponible += $stock;

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
                'producto' => optional($d->product)->name,
                
                'requerido' => $d->cantidad,
                  'entregado' => $entregado, // 
                   'faltante' => $faltante,   // 
                'stock' => $stock,
                'estado' => $estadoItem,
                'tiene_equivalente' => $tieneEquivalente
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
                'estado'           => $estadoCompra
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
            ]
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
