<?php

namespace App\Services\Crm\Orden_servicio;

use App\Models\Crm\Orden_servicio\OrdenServicio;
use App\Models\Crm\Orden_servicio\OrdenServicioDetalle;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use App\Models\Crm\OrdenDetalleObservaciones;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

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

        // actualizar cabecera
        $os->update([
            'empresa_id' => $data['empresa_id'] ?? $os->empresa_id,
            'fecha' => now('America/Bogota'), // actualizar fecha a la fecha actual de Bogota
            'proveedor_id' => $data['proveedor_id'] ?? $os->proveedor_id,
            'observaciones' => $data['observaciones'] ?? $os->observaciones,
        ]);

        foreach ($data['detalles'] as $detalle) {

            // actualizar proceso y observación
            OrdenDetalleObservaciones::where('id', $detalle['observacion_id'])
                ->update([
                    'proceso_bolsas_id' => $detalle['proceso_bolsas_id'],
                    'observacion' => $detalle['observacion'] ?? null,
                    'estado' => $detalle['estado'] ?? 'en_proceso',
                    'usuario_id' => auth()->id(),
                ]);

            // actualizar cantidad en detalle OS
            OrdenServicioDetalle::where('orden_compra_detalle_id', $detalle['orden_compra_detalle_id'])
                ->where('orden_servicio_id', $id)
                ->update([
                    'cantidad' => $detalle['cantidad']
                ]);
        }

        return $os;
    });
}

}


