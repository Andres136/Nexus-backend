<?php

namespace App\Models\contabilidad;

use Illuminate\Database\Eloquent\Model;

class Puck extends Model
{
    protected $table = 'puck';
    protected $fillable = [
        'nombre',
        'numero',
    ];

    public function facturaCompras()
    {
        return $this->hasMany(FacturaCompra::class, 'pucks_id');
    }
}
