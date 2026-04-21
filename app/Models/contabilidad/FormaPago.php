<?php

namespace App\Models\contabilidad;

use Illuminate\Database\Eloquent\Model;

class FormaPago extends Model
{
    protected $table = 'formas_pago';
    protected $fillable = [
        'nombre',
    ];

    public function facturaPagos()
    {
        return $this->hasMany(FacturaPago::class, 'formas_pagos_id');
    }
}
