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
            'proveedor' => $this->proveedor->nombre ?? null,
            'total' => $this->total,
            'estado' => $this->estado->nombre ?? null,

            'detalles' => $this->detalles->map(function ($d) {
                return [
                    'producto_id' => $d->producto_id,
                    'cantidad' => $d->cantidad,
                    'precio' => $d->precio_unitario
                ];
            }),

            'impuestos' => $this->impuestos->map(function ($i) {
                return [
                    'nombre' => $i->nombre,
                    'monto' => $i->pivot->monto
                ];
            }),
         'pdf_url' => $this->pdf_url ? asset($this->pdf_url) : null,
        ];
    }
}
