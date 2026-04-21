<?php

namespace App\Models\contabilidad;

use Illuminate\Database\Eloquent\Model;

class DetalleFacturaCompra extends Model
{
    //
    protected $table = 'detalles_factura_compra';
    protected $fillable = [
        'factura_compras_id',
        'bodega_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'total',

    ];


    public function facturaCompra()
    {
        return $this->belongsTo(FacturaCompra::class, 'factura_compras_id');
    }
}
