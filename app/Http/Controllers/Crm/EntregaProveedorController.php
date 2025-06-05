<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\EntregasResquest;
use App\Models\Crm\EntregaProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;
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
    
    
    
}
