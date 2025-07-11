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
    $query = OrdenCompraProveedor::with(['proveedor', 'usuario', 'estado', 'detalles.entregas'])
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

    // Calculamos el estado_calculado para cada orden
    $ordenes->getCollection()->transform(function ($orden) {
        $detalles = $orden->detalles->map(function ($detalle) {
            $estado = 'Pendiente';
            if ($detalle->cantidad_entregada >= $detalle->cantidad_solicitada) {
                $estado = $detalle->cantidad_entregada > $detalle->cantidad_solicitada
                    ? 'Con entrega extra'
                    : 'Completo';
            }
            return [
                'estado_producto' => $estado
            ];
        });

        $total = $detalles->count();
        $completados = $detalles->whereIn('estado_producto', ['Completo', 'Con entrega extra'])->count();

        $orden->estado_calculado = match (true) {
            $completados === 0 => 'Pendiente',
            $completados < $total => 'Parcialmente Entregada',
            default => 'Completada',
        };

        return $orden;
    });

    return response()->json([
        'message' => 'Lista paginada de órdenes de compra',
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
                        'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
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
             'proveedor_id' => $orden->proveedor_id,
             'usuario' => $orden->usuario->name ?? null,
             'productos' => $detalles,
         ]);
     }
     

    /**
     * Update the specified resource in storage.
     */
    

 
     
     public function updateProveedor(Request $request, $id)
     {
         $request->validate([
             'proveedor_id' => 'required|exists:proveedores,id'
         ]);
     
         $orden = OrdenCompraProveedor::findOrFail($id);
         $orden->proveedor_id = $request->proveedor_id;
         $orden->save();
     
         return response()->json(['message' => 'Proveedor actualizado correctamente.']);
     }
     
    /**
     * Remove the specified resource from storage.
     */
    public function storeDetalle(Request $request)
    {
        $request->validate([
            'orden_id' => 'required|exists:orden_compra_proveedores,id',
            'descripcion' => 'required|string|max:255',
            'cantidad_solicitada' => 'required|numeric|min:0',
            'item' => 'required|integer'
        ]);
    
        $detalle = new OrdenCompraProveedorDetalle();
        $detalle->orden_id = $request->orden_id;
        $detalle->descripcion = $request->descripcion;
        $detalle->cantidad_solicitada = $request->cantidad_solicitada;
        $detalle->item = $request->item;
        $detalle->save();
    
        return response()->json(['message' => 'Detalle creado correctamente.']);
    }
    
    public function update(UpdateOrdenCompraProveedorDetallesRequest $request, $id)
{
    $detalle = OrdenCompraProveedorDetalle::findOrFail($id);

    $request->validate([
        'descripcion' => 'required|string|max:255',
        'cantidad_solicitada' => 'required|numeric|min:0'
    ]);

    $detalle->descripcion = $request->descripcion;
    $detalle->cantidad_solicitada = $request->cantidad_solicitada;
    $detalle->save();

    return response()->json(['message' => 'Detalle actualizado correctamente.']);
}

}
