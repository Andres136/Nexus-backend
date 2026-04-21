<?php

namespace App\Models\contabilidad;

use Illuminate\Database\Eloquent\Model;

class Impuesto extends Model
{
    protected $table = 'impuestos';
    protected $fillable = [
        'nombre',
        'porcentaje',
    ];

    public function facturaCompraImpuestos()
    {
        return $this->hasMany(FacturaCompraImpuesto::class, 'impuestos_id');
    }
}
