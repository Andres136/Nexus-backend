<?php

namespace App\Models\contabilidad;

use App\Models\Crm\product;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use Illuminate\Database\Eloquent\Model;

class DetalleFacturaCompra extends Model
{
    //
    protected $table = 'detalles_factura_compra';
    protected $fillable = [
        'factura_compras_id',
        'bodega_id',
        'producto_id',
        'orden_compra_proveedor_detalle_id',
        'puck_id',
        'cantidad',
        'precio_unitario',
        'total',

    ];


    public function facturaCompra()
    {
        return $this->belongsTo(FacturaCompra::class, 'factura_compras_id');
    }

    public function impuestos()
    {
        return $this->belongsToMany(Impuesto::class, 'detalle_factura_impuestos', 'detalle_factura_id', 'impuesto_id')
            ->withPivot('monto')
            ->withTimestamps();
    }

    public function producto()
    {
        return $this->belongsTo(product::class, 'producto_id');
    }

    public function ordenCompraProveedorDetalle()
    {
        return $this->belongsTo(
            OrdenCompraProveedorDetalle::class,
            'orden_compra_proveedor_detalle_id'
        );
    }
}
