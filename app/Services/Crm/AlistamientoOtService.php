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

        $alistamientos = [];

        if (empty($items)) {
            throw new \Exception("No hay items para procesar");
        }

        //  1. Obtener OT
        $ordenTrabajo = OrdenDeTrabajo::findOrFail($items[0]['orden_trabajo_id']);

        //  2. Obtener Orden de Compra
        $ordenCompra = Orden_Compra::findOrFail($ordenTrabajo->orden_compra_id);

        // 3. Obtener sede correcta
        $sedeId = $ordenCompra->sede_id;

        //  DEBUG (opcional)
        Log::info('DEBUG SEDE', [
            'orden_trabajo_id' => $ordenTrabajo->id,
            'orden_compra_id' => $ordenCompra->id,
            'sede_id' => $sedeId
        ]);

        // 🔥 4. AGRUPAR
        $agrupados = [];
        foreach ($items as $item) {

            if (($item['cantidad'] ?? 0) <= 0) continue;

            $key = $item['producto_id'] . '-' . $item['bodega_id'] . '-' . $sedeId;

            $agrupados[$key] = ($agrupados[$key] ?? 0) + $item['cantidad'];
        }
$errores = [];
        foreach ($agrupados as $key => $cantidadTotal) {

            [$productoId, $bodegaId, $sedeIdKey] = explode('-', $key);

            $inventario = Inventario::where('producto_id', $productoId)
                ->where('bodega_id', $bodegaId)
                ->where('sede_id', $sedeIdKey)
                ->first();

            $producto = product::find($productoId);
            $bodega   = bodega::with('sede')->find($bodegaId);

            if (!$inventario) {
             $errores[] = [
    'producto_id' => $productoId,
    'producto' => $producto->name,
    'bodega_id' => $bodegaId,
    'bodega' => $bodega->nombre,
    'sede' => $bodega->sede->nombre,
    'stock_disponible' => $inventario->stock,
    'ya_alistado' => $totalYaAlistado,
    'solicitado' => $cantidadTotal,
    'mensaje' => "Stock insuficiente para '{$producto->name}' en '{$bodega->nombre}'"
];
                continue;
            }

            // 🔥 YA ALISTADO
        $registroExistente = AlistamientoOt::where([
    'orden_trabajo_id' => $ordenTrabajo->id,
    'producto_id' => $productoId,
    'bodega_id' => $bodegaId,
])->first();

$totalYaAlistado = AlistamientoOt::where('orden_trabajo_id', $ordenTrabajo->id)
    ->where('producto_id', $productoId)
    ->where('bodega_id', $bodegaId)
    ->sum('cantidad');

if ($registroExistente) {
    $totalYaAlistado -= $registroExistente->cantidad; // 🔥 quitar lo actual
}

$totalFinal = $totalYaAlistado + $cantidadTotal;

            if ($totalFinal > $inventario->stock) {
                $errores[] = [
                    'producto_id' => $productoId,
                    'producto' => $producto->name,
                    'bodega_id' => $bodegaId,
                    'bodega' => $bodega->nombre,
                    'sede' => $bodega->sede->nombre,
                    'stock_disponible' => $inventario->stock,
                    'ya_alistado' => $totalYaAlistado,
                    'solicitado' => $cantidadTotal,
                    'mensaje' => "Stock insuficiente para '{$producto->name}' en '{$bodega->nombre}'"
                ];
            }
        }

        // 🔥 6. VALIDAR DETALLE
        foreach ($items as $item) {

            if (($item['cantidad'] ?? 0) <= 0) continue;

            $detalle = Orden_Compra_Detalle::findOrFail($item['orden_compra_detalle_id']);

            $totalActual = AlistamientoOt::where('orden_compra_detalle_id', $detalle->id)
                ->where('producto_id', $item['producto_id'])
                ->sum('cantidad');

            if (($totalActual + $item['cantidad']) > $detalle->cantidad) {
                throw new \Exception("Excede la cantidad requerida");
            }
        }

        if (!empty($errores)) {
    return response()->json([
        'success' => false,
        'errores' => $errores
    ], 400);
}

        // 🔥 7. GUARDAR (SUMAR)
        foreach ($items as $item) {

            if (($item['cantidad'] ?? 0) <= 0) continue;

            $registro = AlistamientoOt::where([
                'orden_trabajo_id' => $item['orden_trabajo_id'],
                'orden_compra_detalle_id' => $item['orden_compra_detalle_id'],
                'producto_id' => $item['producto_id'],
                'bodega_id' => $item['bodega_id'],
            ])->first();

            if ($registro) {
                $registro->increment('cantidad', $item['cantidad']);
                if (isset($item['observacion'])) {
                    $registro->observacion = $item['observacion'];
                    $registro->save();
                }
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