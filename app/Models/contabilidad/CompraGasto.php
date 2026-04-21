<?php

namespace App\Models\contabilidad;

use Illuminate\Database\Eloquent\Model;

class CompraGasto extends Model
{
    protected $table = 'factura_compra_gastos';
    protected $fillable = [
        'factura_compras_id',
        'nombre',
    ];

    public function facturaCompra()
    {
        return $this->belongsTo(FacturaCompra::class, 'factura_compras_id');
    }
}
