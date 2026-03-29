<?php

namespace App\Services\Crm;

use App\Models\Crm\AlistamientoOt;
use App\Models\Crm\bodega;
use App\Models\Crm\Inventario;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\Orden_Compra_Detalle;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Crm\product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlistamientoOtService
{
    //Crear Alistamiento de OT


public function crearAlistamientosMasivo(array $items)
{
    return DB::transaction(function () use ($items) {

        if (empty($items)) {
            throw new \Exception("No hay items para procesar");
        }

        $alistamientos = [];
        $errores = [];

        //  OT y sede
        $ordenTrabajo = OrdenDeTrabajo::findOrFail($items[0]['orden_trabajo_id']);
        $ordenCompra  = Orden_Compra::findOrFail($ordenTrabajo->orden_compra_id);
        $sedeId       = $ordenCompra->sede_id;

        //  AGRUPAR
        $agrupados = [];
        foreach ($items as $item) {
            if (($item['cantidad'] ?? 0) <= 0) continue;

            $key = $item['producto_id'].'-'.$item['bodega_id'].'-'.$sedeId;
            $agrupados[$key] = ($agrupados[$key] ?? 0) + $item['cantidad'];
        }

        //  VALIDAR STOCK
     //  VALIDAR STOCK GLOBAL (CORRECTO)
foreach ($agrupados as $key => $cantidadTotal) {

    [$productoId, $bodegaId, $sedeIdKey] = explode('-', $key);

    $inventario = Inventario::where('producto_id', $productoId)
        ->where('bodega_id', $bodegaId)
        ->where('sede_id', $sedeIdKey)
        ->sum('stock');

    $producto = product::find($productoId);
    $bodega   = bodega::with('sede')->find($bodegaId);

    if (!$inventario) {
        $errores[] = [
            'producto' => $producto->name,
            'bodega' => $bodega->nombre,
            'mensaje' => "No existe inventario"
        ];
        continue;
    }

    //  SUMAR TODO LO YA ALISTADO EN TODAS LAS OT
    $totalGlobal = AlistamientoOt::where('producto_id', $productoId)
        ->where('bodega_id', $bodegaId)
        ->sum('cantidad');

    //  SI ESTÁS EDITANDO → RESTAR EL ACTUAL
    $registroActual = AlistamientoOt::where([
        'orden_trabajo_id' => $ordenTrabajo->id,
        'producto_id' => $productoId,
        'bodega_id' => $bodegaId,
    ])->first();

    if ($registroActual) {
        $totalGlobal -= $registroActual->cantidad;
    }
//  calcular total final primero
$totalFinal = $totalGlobal + $cantidadTotal;

    if ($totalFinal > $inventario) {
        $errores[] = [
            'producto' => $producto->name,
            'bodega' => $bodega->nombre,
            'sede' => $bodega->sede->nombre,
            'stock' => $inventario,
            'ya_alistado_global' => $totalGlobal,
            'solicitado' => $cantidadTotal,
            'mensaje' => "Stock global insuficiente"
        ];
    }
}

        //  VALIDAR DETALLE (BIEN HECHO)
        foreach ($items as $item) {

            if (($item['cantidad'] ?? 0) <= 0) continue;

            $detalle = Orden_Compra_Detalle::findOrFail($item['orden_compra_detalle_id']);

            if ($item['cantidad'] > $detalle->cantidad) {
                throw new \Exception("Excede la cantidad requerida");
            }
        }

      if (!empty($errores)) {
    throw new \Exception(json_encode($errores));
}

        //  GUARDAR (REEMPLAZAR, NO SUMAR)
        foreach ($items as $item) {

            if (($item['cantidad'] ?? 0) <= 0) continue;

            $registro = AlistamientoOt::where([
                'orden_trabajo_id' => $item['orden_trabajo_id'],
                'orden_compra_detalle_id' => $item['orden_compra_detalle_id'],
                'producto_id' => $item['producto_id'],
                'bodega_id' => $item['bodega_id'],
            ])->first();

            if ($registro) {
                //  REEMPLAZAR
                $registro->cantidad = $item['cantidad'];
                $registro->observacion = $item['observacion'] ?? null;
                $registro->save();
            } else {
                $registro = AlistamientoOt::create([
                    'orden_trabajo_id' => $item['orden_trabajo_id'],
                    'orden_compra_detalle_id' => $item['orden_compra_detalle_id'],
                    'producto_id' => $item['producto_id'],
                    'bodega_id' => $item['bodega_id'],
                    'cantidad' => $item['cantidad'],
                    'tipo' => $item['tipo'],
                    'observacion' => $item['observacion'] ?? null,
                    'usuario_id' => auth()->id(),
                    'fecha_alistamiento' => now(),
                ]);
            }

            $alistamientos[] = $registro;
        }

        return $alistamientos;
    });
}

    // Obtener Alistamientos por OT
  public function getByOrdenTrabajo($id)
{
    $alistamientos = AlistamientoOt::with([
        'producto',
        'bodega.sede'
    ])
    ->where('orden_trabajo_id', $id)
    ->get();

    return response()->json([
        'success' => true,
        'data' => $alistamientos
    ]);
}
}