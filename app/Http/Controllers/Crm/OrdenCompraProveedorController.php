<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\OrdenCompraProveedorRequest;
use App\Http\Requests\Crm\UpdateOrdenCompraProveedorDetallesRequest;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class OrdenCompraProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = OrdenCompraProveedor::with(['proveedor', 'usuario', 'estado'])
                    ->orderBy('id', 'desc');
    
        if ($request->has('search')) {
            $search = $request->search;
    
            $query->where(function($q) use ($search) {
                $q->where('numero_orden', 'LIKE', "%$search%")
                  ->orWhereHas('proveedor', function($q) use ($search) {
                      $q->where('nombre', 'LIKE', "%$search%");
                  });
            });
        }
    
        $ordenes = $query->paginate(10);
    
        return response()->json([
            'message' => 'Lista paginada de ordenes de compra',
            'ordenes' => $ordenes
        ], 200);
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(OrdenCompraProveedorRequest $request)
    {
        DB::beginTransaction();

        try {
            $ordenCompra = OrdenCompraProveedor::create([
                'proveedor_id' => $request->proveedor_id,
                'fecha' => $request->fecha,
                'numero_orden' => $request->numero_orden,
                'estado_id' => 1, // Estado inicial
                'usuario_id' => auth()->id(),
               // 'usuario_id' => $request->usuario_id, // Si se desea permitir la asignación de un usuario diferente
                'observaciones' => $request->observaciones,
            ]);

            foreach ($request->detalles as $i=> $detalle) {
                $ordenCompra->detalles()->create([
                    'item' => $i + 1,
                    'descripcion' => $detalle['descripcion'],
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'cantidad_entregada' => $detalle['cantidad_entregada'] ?? 0,
                ]);

            }

            DB::commit();

            return response()->json(['message' => 'Orden de compra creada con éxito.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al crear la orden de compra.',
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
    ], 500);
        }
        
    } 

    /**
     * Display the specified resource.
     */      //Consultar una orden y su estado actual (con detalles y análisis de cantidades entregadas vs solicitadas).
     public function show($id)
     {
         $orden = OrdenCompraProveedor::with(['proveedor', 'usuario', 'estado', 'detalles.entregas',])->findOrFail($id);
     
         $detalles = $orden->detalles->map(function ($detalle) {
            $estado = 'Pendiente';
        
            if ($detalle->cantidad_entregada >= $detalle->cantidad_solicitada) {
                $estado = $detalle->cantidad_entregada > $detalle->cantidad_solicitada
                    ? 'Con entrega extra'
                    : 'Completo';
            }
        
            return [
                'id' => $detalle->id,
                'item' => $detalle->item,
                'descripcion' => $detalle->descripcion,
                'cantidad_solicitada' => (float) $detalle->cantidad_solicitada,
                'cantidad_entregada' => (float) $detalle->cantidad_entregada,
                'estado_producto' => $estado,
                'updated_at' => $detalle->updated_at,
                'entregas' => $detalle->entregas->map(function ($entrega) {
                    return [
                        'id' => $entrega->id,
                        'cantidad_entregada' => (float) $entrega->cantidad_entregada,
                        'fecha_entrega' => $entrega->fecha_entrega->format('Y-m-d H:i:s'),
                        'observaciones' => $entrega->observaciones,
                    ];
                }),
            ];
        });
        
     
         $total = $detalles->count();
         $completados = $detalles->whereIn('estado_producto', ['Completo', 'Con entrega extra'])->count();
     
         $estado_orden = match (true) {
             $completados === 0 => 'Pendiente',
             $completados < $total => 'Parcialmente Entregada',
             default => 'Completada',
         };
     
         return response()->json([
             'id' => $orden->id,
             'numero_orden' => $orden->numero_orden,
             'fecha' => $orden->fecha,
             'observaciones' => $orden->observaciones,
             'estado_registrado' => $orden->estado->nombre,
             'estado_calculado' => $estado_orden,
             'proveedor' => $orden->proveedor->nombre,
             'usuario' => $orden->usuario->name ?? null,
             'productos' => $detalles,
         ]);
     }
     

    /**
     * Update the specified resource in storage.
     */
    

    public function update(UpdateOrdenCompraProveedorDetallesRequest $request, string $id)
{
    DB::beginTransaction();

    try {
        foreach ($request->detalles as $item) {
            $detalle = OrdenCompraProveedorDetalle::find($item['id']);

            // Acumular entrega
            $detalle->cantidad_entregada += $item['cantidad_entregada'];
            $detalle->save();
        }

        // Obtener la orden y todos sus detalles
        $orden = OrdenCompraProveedor::with('detalles')->findOrFail($id);

        // Verificar si todos los ítems están completos
        $estadoCompleto = $orden->detalles->every(function ($detalle) {
            return $detalle->cantidad_entregada >= $detalle->cantidad_solicitada;
        });

        // Actualizar estado de la orden
        $orden->estado_id = $estadoCompleto ? 2 : 1;
        $orden->save();

        DB::commit();

        return response()->json(['message' => 'Entrega registrada correctamente.']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Error al actualizar la entrega.',
            'detalles' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
