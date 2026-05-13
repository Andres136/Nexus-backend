<?php

namespace App\Models\contabilidad;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FacturaPago extends Model
{
    protected $table = 'factura_pagos';
    protected $fillable = [
        'factura_compras_id',
        'forma_pago_id',
        'monto',
        'fecha_pago',
        'observaciones',
        'user_id'
    ];

    public function facturaCompra()
    {
        return $this->belongsTo(FacturaCompra::class, 'factura_compras_id');
    }

    public function formaPago()
    {
        return $this->belongsTo(FormaPago::class, 'forma_pago_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
