<?php

namespace App\Services\Crm;

use App\Models\Crm\Inventario;
use App\Models\Crm\product;

class ProductQueryService
{
   public function inventariosDisponibles($request)
{
    $query = Inventario::with('producto')
        ->where('stock', '>', 0);

    if ($request->empresa_id) {
        $query->where('empresa_id', $request->empresa_id);
    }
    if ($request->sede_id) {
        $query->where('sede_id', $request->sede_id);
    }

    if ($request->categoria_id) {
        $query->whereHas('producto', function ($q) use ($request) {
            $q->where('categoria_id', $request->categoria_id);
        });
    }

    return response()->json([
        'data' => $query->get()->map(function ($inv) {
            return [
                'inventario_id' => $inv->id,
                'producto_id' => $inv->producto->id,
                'nombre' => $inv->producto->name,
                'code' => $inv->producto->code,
                'stock' => $inv->stock
            ];
        })
    ]);
}

}