<?php

namespace App\Models\contabilidad;

use Illuminate\Database\Eloquent\Model;

class FacturaPago extends Model
{
    protected $table = 'factura_pagos';
    protected $fillable = [
        'factura_compras_id',
        'forma_pago_id',
        'monto',
    ];

    public function facturaCompra()
    {
        return $this->belongsTo(FacturaCompra::class, 'factura_compras_id');
    }

    public function formaPago()
    {
        return $this->belongsTo(FormaPago::class, 'forma_pago_id');
    }
}
