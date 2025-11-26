<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\EntregasRequest;

use App\Models\Crm\EntregaProveedor;
use App\Models\Crm\Inventario;
use App\Models\Crm\Orden_Compra_Detalle;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PgSql\Lob;

class EntregaProveedorController extends Controller
{

  public function store(EntregasRequest $request)
{

    

        $entrega = DB::transaction(function () use ($request) {
            // 1. Crear la entrega
            $user = auth()->user();
            $entrega = EntregaProveedor::create([
                'detalle_id'        => $request->detalle_id,
                'cantidad_entregada'=> $request->cantidad_entregada,
                'fecha_entrega'     => $request->fecha_entrega, 
                'observaciones'     => $request->observaciones,
                'bodega_id'         => $request->bodega_id,
                'producto_id'       => $request->producto_id,
                'user_id'           => $user->id,
                'sede_id'          => $user->sede_id,
            ]);
        
            // 2. Sumar la cantidad entregada al detalle principal
            $detalle = OrdenCompraProveedorDetalle::findOrFail($request->detalle_id);
            $detalle->cantidad_entregada += $request->cantidad_entregada;
            $detalle->save();
        
            // 3. Verificar si todos los detalles están completamente entregados
            $orden = $detalle->orden; 
            $todosCompletos = $orden->detalles->every(function ($d) {
                return $d->cantidad_entregada >= $d->cantidad_solicitada;
            });
        
            // 4. Si todos están completos, actualizar estado de la orden
            if ($todosCompletos) {
                $orden->estado_id = 2; // Estado COMPLETO
                $orden->save();
            }

            // 5. ACTUALIZAR INVENTARIO (desempaquetando del request)
            $inventarioData = $request->input('inventario', []); // viene del frontend

            if (!empty($inventarioData)) {
                $productoId = $inventarioData['producto_id'] ?? null;
                $empresaId  = $inventarioData['empresa_id'] ?? null;
                $sedeId     = $inventarioData['sede_id'] ?? null;
                $bodegaId   = $inventarioData['bodega_id'] ?? null;
                $stock      = $inventarioData['stock'] ?? 0;

                if ($productoId && $empresaId && $sedeId && $bodegaId) {
                    $inventario = Inventario::firstOrCreate(
                        [
                            'producto_id' => $productoId,
                            'empresa_id'  => $empresaId,
                            'sede_id'     => $sedeId,
                            'bodega_id'   => $bodegaId,
                        ],
                        ['stock' => 0]
                    );
 

                    $inventario->stock += (float) $stock;
                    $inventario->save();
                }
            }

            return [
                'entrega' => $entrega,
                'detalle' => $detalle
            ];
        });

        return response()->json([
            'message' => 'Entrega registrada correctamente',
            'entrega' => $entrega['entrega'],
            'detalle_actualizado' => $entrega['detalle'],
        ], 201);


}



public function update(EntregasRequest $request, $id)
{
    $user = auth()->user();
    try {
        $entrega = EntregaProveedor::findOrFail($id);

        $cantidadAnterior = $entrega->cantidad_entregada;

        $entrega->update([
            'cantidad_entregada' => $request->cantidad_entregada,
            'fecha_entrega'      => $request->fecha_entrega,
            'observaciones'      => $request->observaciones,
            'bodega_id'          => $request->bodega_id,
            'producto_id'        => $request->producto_id,
            'user_id'            => $user->id,
            'sede_id'            => $user->sede_id,
        ]);

        // ✅ aseguramos producto_id aunque no venga del front
        $productoId = $request->producto_id;
        if (!$productoId && $request->detalle_id) {
            $detalle = OrdenCompraProveedorDetalle::find($request->detalle_id);
            if ($detalle) {
                $productoId = $detalle->producto_id;
            }
        }

        $empresaId  = $request->empresa_id;
        $sedeId     = $request->sede_id;
        $bodegaId   = $request->bodega_id;

        if ($productoId && $empresaId && $sedeId && $bodegaId) {
            $inventario = Inventario::firstOrCreate(
                [
                    'producto_id' => $productoId,
                    'empresa_id'  => $empresaId,
                    'sede_id'     => $sedeId,
                    'bodega_id'   => $bodegaId,
                ],
                ['stock' => 0]
            );

            $inventario->stock = ($inventario->stock - $cantidadAnterior) + $request->cantidad_entregada;
            if ($inventario->stock < 0) {
                $inventario->stock = 0;
            }
            $inventario->save();
        }

        return response()->json([
            'message' => 'Entrega e inventario actualizados correctamente',
            'entrega' => $entrega,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error al actualizar entrega e inventario',
            'detalle' => $e->getMessage(),
        ], 500);
    }
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


    public function referenciasFaltantes()
{
    $ordenes = \App\Models\Crm\OrdenCompraProveedor::with(['detalles', 'proveedor'])->get();

    $faltantes = collect();

    foreach ($ordenes as $orden) {
        $faltantesOrden = $orden->detalles->filter(function ($detalle) {
            return $detalle->cantidad_entregada < $detalle->cantidad_solicitada;
        })->map(function ($detalle) use ($orden) {
            return [
                'orden_id' => $orden->id,
                'numero_orden' => $orden->numero_orden,
                'fecha_orden' => $orden->fecha,
                'proveedor' => $orden->proveedor->nombre ?? 'N/A',
                'descripcion' => $detalle->descripcion,
                'cantidad_solicitada' => $detalle->cantidad_solicitada,
                'cantidad_entregada' => $detalle->cantidad_entregada,
                'cantidad_faltante' => $detalle->cantidad_solicitada - $detalle->cantidad_entregada,
                'item' => $detalle->item,
                'porcentaje_entregado' => $detalle->cantidad_solicitada > 0 
                    ? round(($detalle->cantidad_entregada / $detalle->cantidad_solicitada) * 100, 2)
                    : 0,
            ];
        });

        $faltantes = $faltantes->merge($faltantesOrden);
    }

    return response()->json([
        'referencias_faltantes' => $faltantes->values(),
     
    ]);
}
    
    public function updateDetalle(Request $request, $id)
{
    $detalle = OrdenCompraProveedorDetalle::findOrFail($id);
    $detalle->update([
        'descripcion' => $request->descripcion,
        'cantidad_solicitada' => $request->cantidad_solicitada,
        'cantidad_entregada' => $request->cantidad_entregada,
        'proceso_bolsas_id' => $request->proceso_bolsas_id,
        'proveedor_id' => $request->proveedor_id,
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