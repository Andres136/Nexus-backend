<?php

namespace App\Http\Resources\contabilidad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacturaCompraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
  public function toArray($request)
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero_factura,
            'numero_factura_proveedor' => $this->numero_factura_proveedor,
            'proveedor' => $this->proveedor->nombre ?? null,
            'empresa' => $this->empresa->nombre ?? null,
            'fecha_emision' => $this->fecha_emision,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'subtotal' => $this->subtotal,
            'total_impuestos' => $this->total_impuestos,
            'total_gastos' => $this->total_gastos,
            'total' => $this->total,
            'saldo_pendiente' => $this->saldo_pendiente,
            'observaciones' => $this->observaciones,
            'estado' => $this->estado->nombre ?? null,

            'detalles' => $this->detalles->map(function ($d) {
                return [
                    'producto_id' => $d->producto_id,
                    'producto' => $d->producto->name ?? null,
                    'cantidad' => $d->cantidad,
                    'precio' => $d->precio_unitario,
                    'total' => $d->total,
                ];
            }),

            'impuestos' => $this->impuestos->map(function ($i) {
                return [
                    'nombre' => $i->nombre,
                    'operacion' => $i->operacion?->value,
                    'monto' => $i->pivot->monto
                ];
            }),
         'pdf_url' => $this->pdf_url ? asset($this->pdf_url) : null,
        ];
    }
}
