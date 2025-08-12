<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\EntregasResquest;
use App\Models\Crm\EntregaProveedor;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class EntregaProveedorController extends Controller
{
    public function store(EntregasResquest $request)
    {
        // 1. Crear la entrega
        $entrega = EntregaProveedor::create([
            'detalle_id' => $request->detalle_id,
            'cantidad_entregada' => $request->cantidad_entregada,
            'fecha_entrega' => $request->fecha_entrega, 
            'observaciones' => $request->observaciones,
        ]);
    
        // 2. Sumar la cantidad entregada al detalle principal
        $detalle = OrdenCompraProveedorDetalle::find($request->detalle_id);
        $detalle->cantidad_entregada += $request->cantidad_entregada;
        $detalle->save();
    
        // 3. Verificar si todos los detalles están completamente entregados
        $orden = $detalle->orden; // 👈 esta es tu relación definida
        $todosCompletos = $orden->detalles->every(function ($d) {
            return $d->cantidad_entregada >= $d->cantidad_solicitada;
        });
    
        // 4. Si todos están completos, actualizar estado de la orden
        if ($todosCompletos) {
            $orden->estado_id = 2; // Estado COMPLETO
            $orden->save();
        }
    
        return response()->json([
            'message' => 'Entrega registrada correctamente',
            'entrega' => $entrega,
            'detalle_actualizado' => $detalle,
        ]);
    }
    
    public function update(EntregasResquest $request, $id)
    {
        // 1. Buscar la entrega por ID
        $entrega = EntregaProveedor::findOrFail($id);
    
        // 2. Actualizar los campos de la entrega
        $entrega->update([
            'detalle_id' => $request->detalle_id,
            'cantidad_entregada' => $request->cantidad_entregada,
            'fecha_entrega' => $request->fecha_entrega,
            'observaciones' => $request->observaciones,
        ]);
    
        // 3. Buscar el detalle actualizado y recalcular total entregado
        $detalle = OrdenCompraProveedorDetalle::find($request->detalle_id);
    
        // Aquí NO se suma directamente: se recalcula con todas las entregas
        $totalEntregado = $detalle->entregas()->sum('cantidad_entregada');
        $detalle->cantidad_entregada = $totalEntregado;
        $detalle->save();
    
        // 4. Cargar la orden relacionada con sus detalles
  // 4. Cargar la orden relacionada con sus detalles
$orden = $detalle->orden;
$orden->load('detalles');

// 5. Evaluar si todos los ítems están completamente entregados
$todosCompletos = $orden->detalles->every(function ($d) {
    return $d->cantidad_entregada >= $d->cantidad_solicitada;
});

// 6. Actualizar el estado de la orden solo si aplica
if ($todosCompletos && $orden->estado_id !== 2) {
    $orden->estado_id = 2; // COMPLETO
    $orden->save();
} elseif (!$todosCompletos && $orden->estado_id === 2) {
    $orden->estado_id = 1; // Volver a PENDIENTE si alguien bajó entregas
    $orden->save();
}

    
        return response()->json([
            'message' => 'Entrega actualizada correctamente',
            'entrega' => $entrega,
            'detalle_actualizado' => $detalle,
        ]);
    }
    public function referenciasExcedidas()
    {
        $ordenes = \App\Models\Crm\OrdenCompraProveedor::with(['detalles', 'proveedor'])->get();
    
        $excedidos = collect();
    
        foreach ($ordenes as $orden) {
            $excedidosOrden = $orden->detalles->filter(function ($detalle) {
                return $detalle->cantidad_entregada > $detalle->cantidad_solicitada;
            })->map(function ($detalle) use ($orden) {
                return [
                    'orden_id' => $orden->id,
                    'numero_orden' => $orden->numero_orden,
                    'fecha_orden' => $orden->fecha,
                    'proveedor' => $orden->proveedor->nombre ?? 'N/A',
                    'descripcion' => $detalle->descripcion,
                    'cantidad_solicitada' => $detalle->cantidad_solicitada,
                    'cantidad_entregada' => $detalle->cantidad_entregada,
                    'excedente' => $detalle->cantidad_entregada - $detalle->cantidad_solicitada,
                    'item' => $detalle->item,
                ];
            });
    
            $excedidos = $excedidos->merge($excedidosOrden);
        }
    
        return response()->json([
            'referencias_excedidas' => $excedidos->values(),
        ]);
    }
    
    public function updateDetalle(Request $request, $id)
{
    $detalle = OrdenCompraProveedorDetalle::findOrFail($id);
    $detalle->update([
        'descripcion' => $request->descripcion,
        'cantidad_solicitada' => $request->cantidad_solicitada,
     
    ]);

    return response()->json(['mensaje' => 'Detalle actualizado correctamente']);
}

    //Eliminar item de la orden 

   public function eliminarItem(Request $request, $id)
{
    $detalle = OrdenCompraProveedorDetalle::findOrFail($id);

    // Validación extra si necesitas proteger entregas ya realizadas
    if ($detalle->entregas()->exists()) {
        return response()->json([
            'message' => 'No se puede eliminar: ya tiene entregas registradas.'
        ], 422);
    }

    $detalle->delete();

    return response()->json(['message' => 'Ítem eliminado correctamente']);
}

public function descargarPendientes(Request $request)
{
    $proveedorId = $request->query('proveedor_id');

    $detalles = OrdenCompraProveedorDetalle::with(['orden.proveedor'])
        ->whereColumn('cantidad_entregada', '<', 'cantidad_solicitada')
        ->when($proveedorId, fn ($q) => $q->whereHas('orden', fn ($qq) =>
            $qq->where('proveedor_id', $proveedorId)
        ))
        ->get();

    if ($detalles->isEmpty()) {
        return response()->json(['mensaje' => 'No hay ítems pendientes.'], 404);
    }

    $itemsPendientes = $detalles->map(function ($d) {
        return [
            'orden_id'            => $d->orden->id,
            'numero_orden'        => $d->orden->numero_orden,
            'fecha_orden'         => $d->orden->fecha,
            'proveedor'           => $d->orden->proveedor->nombre ?? 'N/A',
            'item'                => $d->item,
            'descripcion'         => $d->descripcion,
            'cantidad_solicitada' => $d->cantidad_solicitada,
            'cantidad_entregada'  => $d->cantidad_entregada,
            'pendiente'           => $d->cantidad_solicitada - $d->cantidad_entregada,
        ];
    });

    $pdf = Pdf::loadView('pdf.items_pendientes', [
        'items'    => $itemsPendientes,
        'generado' => now()->format('Y-m-d H:i:s'),
    ]);

    return response($pdf->output(), 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="items_pendientes.pdf"',
    ]);
}

}