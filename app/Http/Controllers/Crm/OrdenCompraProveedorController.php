<?php

namespace App\Http\Controllers\Crm;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\OrdenCompraProveedorRequest;
use App\Http\Requests\Crm\UpdateOrdenCompraProveedorDetallesRequest;
use App\Mail\OrdenCompraProveedorMail;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use Barryvdh\DomPDF\Facade\Pdf ;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrdenCompraProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = OrdenCompraProveedor::with(['proveedor', 'usuario', 'estado', 'detalles.entregas', 'detalles.procesoBolsas', 'detalles.proveedor', 'empresa'])
            ->orderBy('id', 'desc');

        if ($request->has('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('numero_orden', 'LIKE', "%$search%")
                    ->orWhereHas('proveedor', function ($q) use ($search) {
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
        // Generar número de orden consecutivo
        $ultimaOrden = OrdenCompraProveedor::orderBy('id', 'desc')->first();
        $numeroConsecutivo = $ultimaOrden ? $ultimaOrden->id + 1 : 1;
        $numeroOrden = 'OC-' . str_pad($numeroConsecutivo, 3, '0', STR_PAD_LEFT);
        try {
            $ordenCompra = OrdenCompraProveedor::create([
                'proveedor_id' => $request->proveedor_id,
                'fecha' =>now() ,
                'numero_orden' => $numeroOrden,
                'estado_id' => 1, // Estado inicial
                'usuario_id' => auth()->id(),
                // 'usuario_id' => $request->usuario_id, // Si se desea permitir la asignación de un usuario diferente
                'observaciones' => $request->observaciones,
                'empresa_id' => $request->empresa_id,
                'bodega_id' => $request->bodega_id,
                'sede_id' => $request->sede_id,
            ]);

            foreach ($request->detalles as $i => $detalle) {
                $ordenCompra->detalles()->create([
                    'item' => $i + 1,
                    'descripcion' => $detalle['descripcion'],
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'cantidad_entregada' => $detalle['cantidad_entregada'] ?? 0,
                    'proveedor_id' => $detalle['proveedor_id'] ?? null, // Aseguramos que este campo sea nullable
                    'proceso_bolsas_id' => $detalle['proceso_bolsas_id'] ?? null, // Aseguramos que este campo sea nullable
                    'code' => $detalle['code'] ?? null, // Nuevo campo código
                    'producto_id' => $detalle['producto_id'] ?? null, // Nuevo campo producto_id
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Orden de compra creada con éxito.',
               'orden' => $ordenCompra
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al crear la orden de compra.',
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
        $orden = OrdenCompraProveedor::with(['proveedor', 'usuario', 'estado', 'detalles.entregas', 'detalles.procesoBolsas', 'detalles.proveedor','empresa'])->findOrFail($id);

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
                'proveedor_id'          => $detalle->proveedor_id,
                'proveedor_nombre'      => $detalle->proveedor?->nombre,           // ✅
                'proceso_bolsas_id'     => $detalle->proceso_bolsas_id,
                'proceso_bolsas_nombre' => $detalle->procesoBolsas?->nombre,       // ✅

                'entregas' => $detalle->entregas->map(function ($entrega) {
                    return [
                        'id' => $entrega->id,
                        'cantidad_entregada' => (float) $entrega->cantidad_entregada,
                        'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                        'observaciones' => $entrega->observaciones,
                        'proveedor_id'       => $entrega->proveedor_id,
       
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
            'empresa' => $orden->empresa,
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
            'item' => 'required|integer',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'proceso_bolsas_id' => 'nullable|exists:proceso_bolsas,id',

        ]);

        $detalle = new OrdenCompraProveedorDetalle();
        $detalle->orden_id = $request->orden_id;
        $detalle->descripcion = $request->descripcion;
        $detalle->cantidad_solicitada = $request->cantidad_solicitada;
        $detalle->item = $request->item;
        $detalle->proveedor_id = $request->proveedor_id ?? null; // Aseguramos que este campo sea nullable
        $detalle->proceso_bolsas_id = $request->proceso_bolsas_id ?? null; // Aseguramos que este campo sea nullable

        $detalle->save();

        return response()->json(['message' => 'Detalle creado correctamente.']);
    }

    public function update(UpdateOrdenCompraProveedorDetallesRequest $request, $id)
    {
        $detalle = OrdenCompraProveedorDetalle::findOrFail($id);


        $detalle->descripcion = $request->descripcion;
        $detalle->cantidad_solicitada = $request->cantidad_solicitada;
        $detalle->item = $request->item;
        $detalle->proveedor_id = $request->proveedor_id ?? null; // Aseguramos que este campo sea nullable
        $detalle->proceso_bolsas_id = $request->proceso_bolsas_id ?? null; // Aseguramos que este campo sea nullable

        $detalle->save();

        return response()->json(['message' => 'Detalle actualizado correctamente.']);
    }


    //Eliminar un detalle de orden de compra
    public function destroy($id)
    {
        $detalle = OrdenCompraProveedorDetalle::findOrFail($id);
        $detalle->delete();

        return response()->json(['message' => 'Detalle eliminado correctamente.']);
    
    }

    //Descargar la orden de compra del proveedor en pdf
    public function descargarOrdenPdfProveedor($id)
    {
        $orden = OrdenCompraProveedor::with(['proveedor', 
        'empresa',
         'usuario',
         'estado',
         'detalles.entregas',
         'detalles.procesoBolsas',
         'detalles.proveedor'])->findOrFail($id);


  // Convertir logo a base64 si existe
    if ($orden->empresa && $orden->empresa->logo) {
        $logoPath = storage_path('app/public/' . $orden->empresa->logo);
        
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoMimeType = mime_content_type($logoPath);
            $orden->empresa->logo_base64 = "data:{$logoMimeType};base64,{$logoData}";
        }
    }


        $pdf = Pdf::loadView('pdf.orden_compra_proveedor', compact('orden'));
        return $pdf->download('orden_compra_proveedor_' . $orden->numero_orden . '.pdf');
    }   
    

    //Enviar la orden de compra por correo electrónico al proveedor
    public function enviarEmail($id)
    {
        $orden = OrdenCompraProveedor::with(['proveedor', 'empresa', 'usuario', 'estado', 'detalles.entregas', 'detalles.procesoBolsas', 'detalles.proveedor'])->findOrFail($id);

        // Generar el PDF
        $pdf = Pdf::loadView('pdf.orden_compra_proveedor', compact('orden'))->output();

        // Enviar el correo
        Mail::to($orden->proveedor->correo)->send(new OrdenCompraProveedorMail($orden, $pdf));

        return response()->json(['message' => 'Correo enviado correctamente al proveedor.']);
    }
}
