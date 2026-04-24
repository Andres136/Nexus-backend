<?php

namespace App\Services\Crm;

use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenComprasHistorial;
use Illuminate\Support\Facades\DB;

class OrdenCompraHistorialService
{
    //

public function actualizarFechaOrden($data)
{
    DB::beginTransaction();

    try {
        $orden = Orden_Compra::findOrFail($data['orden_compra_id']);

        $fechaAnterior = $orden->fecha_entrega;

        // 🔥 Solo si cambia
        if ($fechaAnterior != $data['fecha_nueva']) {

            // ✅ 1. ACTUALIZAR ORDEN
            $orden->fecha_entrega = $data['fecha_nueva'];
            $orden->save();

            // ✅ 2. GUARDAR HISTORIAL
            OrdenComprasHistorial::create([
                'orden_compra_id' => $orden->id,
                'fecha_anterior' => $fechaAnterior,
                'fecha_nueva' => $data['fecha_nueva'],
                'observacion' => $data['observacion'] ?? null,
                'usuario_id' => auth()->id(),
            ]);
        }

        DB::commit();

        return $orden;

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
}