<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class CotizacionDetalles extends Model
{
    protected $fillable = [
        'cotizacion_id',
        'item',
        'largo_cm',
        'ancho_cm',
        'calibre',
        'peso_bolsa',
        'numero_bolsas',
        'cantidad',
        'precio_total',
        'valor_unitario',
        'valor_total',
        'cantidad_requerida_kg',
        'descripcion',
        'cliente_clb',
        'observaciones'
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
