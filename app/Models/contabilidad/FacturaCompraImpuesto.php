<?php

namespace App\Models\contabilidad;

use Illuminate\Database\Eloquent\Model;

class FacturaCompraImpuesto extends Model
{
    protected $table = 'factura_compra_impuestos';
    protected $fillable = [
        'factura_compras_id',
        'impuestos_id',
        'monto',
    ];

    public function facturaCompra()
    {
        return $this->belongsTo(FacturaCompra::class, 'factura_compras_id');
    }

    public function impuesto()
    {
        return $this->belongsTo(Impuesto::class, 'impuestos_id');
    }
}
