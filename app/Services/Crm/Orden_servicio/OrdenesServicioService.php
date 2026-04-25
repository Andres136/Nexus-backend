<?php

namespace App\Services\Crm\Orden_servicio;

use App\Models\Crm\Orden_servicio\OrdenServicio;
use App\Models\Crm\Orden_servicio\OrdenServicioDetalle;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use App\Models\Crm\OrdenDetalleObservaciones;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrdenesServicioService
{
    //Crear orden de servicio
 public function createOrdenServicio(array $data): OrdenServicio
{
    return DB::transaction(function () use ($data) {

        $os = OrdenServicio::create([
            'numero_os' => $this->generarNumero(),
            'empresa_id' => $data['empresa_id'],
            'fecha' => $data['fecha'],
            'proveedor_id' => $data['proveedor_id'],
            'estado' => 'pendiente',
            'usuario_id' => auth()->id(),
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        foreach ($data['detalles'] as $detalle) {
            $os->detalles()->create([
                'orden_compra_detalle_id' => $detalle['orden_compra_detalle_id'],
                'cantidad' => $detalle['cantidad'],
            ]);

            $this->marcarDetalleEnProceso($detalle['orden_compra_detalle_id']);
        }

        return $os;
    });
}


    //Generar número de orden de servicio
    private function generarNumero()
    {        $ultimo = OrdenServicio::latest()->first();
        if (!$ultimo) {
            return 'OS-0001';
        }
        $numero = intval(substr($ultimo->numero_os, 3)) + 1;
        return 'OS-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
private function marcarDetalleEnProceso(int $detalleId): void
{
    OrdenDetalleObservaciones::where('orden_detalle_id', $detalleId)
        ->update(['estado' => 'en_proceso']);
}
    //Consultar con filtros de id producto cual esta en una orden de compra detalle

    //Crear pdf de orden de servicio

public function generarPdf(OrdenServicio $ordenServicio)
{
    $ordenServicio->load(
        'empresa',
        'proveedor',
        'detalles.ordenCompraDetalle.producto',
        'detalles.ordenCompraDetalle.observaciones.proceso',
        'detalles.ordenCompraDetalle.observaciones.usuario'
    );

    $pdf = Pdf::loadView('pdf.orden_servicio', compact('ordenServicio'));

    return $pdf;
}
//Obtener  ordenes de servicio con filtros de fecha, proveedor y estado y paginacion
public function obtenerOrdenesServicio(array $filtros)
{
    $query = OrdenServicio::with('proveedor','empresa');

    if (isset($filtros['fecha_inicio']) && isset($filtros['fecha_fin'])) {
        $query->whereBetween('fecha', [$filtros['fecha_inicio'], $filtros['fecha_fin']]);
    }

    if (isset($filtros['proveedor_id'])) {
        $query->where('proveedor_id', $filtros['proveedor_id']);
    }

    if (isset($filtros['estado'])) {
        $query->where('estado', $filtros['estado']);
    }
    

    return $query->orderBy('created_at','desc')->paginate(10);
}
public function actualizarOrdenServicio(int $id, array $data)
{
    return DB::transaction(function () use ($id, $data) {

        $os = OrdenServicio::findOrFail($id);

        $os->update([
            'empresa_id' => $data['empresa_id'] ?? $os->empresa_id,
            'fecha' => now('America/Bogota'),
            'proveedor_id' => $data['proveedor_id'] ?? $os->proveedor_id,
            'observaciones' => $data['observaciones'] ?? $os->observaciones,
        ]);


        $detallesPayload = collect($data['detalles'] ?? []);

        // 1) Crear o actualizar cada detalle recibido
        foreach ($detallesPayload as $detalle) {
            $detalleOs = OrdenServicioDetalle::updateOrCreate(
                [
                    'orden_servicio_id' => $id,
                    'orden_compra_detalle_id' => $detalle['orden_compra_detalle_id'],
                ],
                [
                    'cantidad' => $detalle['cantidad'],
                ]
            );


$detalleOs->cantidad = $detalle['cantidad'];
$detalleOs->save();

            // 2) Observación: actualizar si viene id, crear si no viene
            if (!empty($detalle['observacion_id'])) {
          $obs = OrdenDetalleObservaciones::find($detalle['observacion_id']);



$obs->proceso_bolsas_id = $detalle['proceso_bolsas_id'];
$obs->observacion = $detalle['observacion'] ?? 'Sin observación';
$obs->estado = $detalle['estado'] ?? 'en_proceso';
$obs->usuario_id = auth()->id();

$obs->save();


            } else {
                OrdenDetalleObservaciones::create([
                    'orden_detalle_id' => $detalle['orden_compra_detalle_id'],
                    'proceso_bolsas_id' => $detalle['proceso_bolsas_id'] ?? null,
                    'observacion' => $detalle['observacion'] ?? 'Proceso registrado sin observación',
                    'estado' => $detalle['estado'] ?? 'en_proceso',
                    'usuario_id' => auth()->id(),
                    'proveedor_id' => $os->proveedor_id,
                ]);
            }
        }

        // 3) Eliminar detalles que ya no vienen en payload (sincronización real)
        $idsPayload = $detallesPayload->pluck('orden_compra_detalle_id')->filter()->values()->all();

        OrdenServicioDetalle::where('orden_servicio_id', $id)
            ->whereNotIn('orden_compra_detalle_id', $idsPayload)
            ->delete();

        return $os->fresh('detalles.ordenCompraDetalle.observaciones');
    });
 
}

}


