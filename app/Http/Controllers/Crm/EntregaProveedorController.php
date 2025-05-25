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

        return response()->json([
            'message' => 'Entrega registrada correctamente',
            'entrega' => $entrega,
            'detalle_actualizado' => $detalle
        ]);
    }

}
