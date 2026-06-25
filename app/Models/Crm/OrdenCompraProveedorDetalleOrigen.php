<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class OrdenCompraProveedorDetalleOrigen extends Model
{
    protected $table = 'orden_compra_proveedor_detalle_origenes';

    protected $fillable = [
        'orden_compra_proveedor_detalle_id',
        'orden_compra_id',
        'orden_compra_detalle_id',
        'producto_id',
        'sede_id',
        'bodega_id',
        'cantidad_solicitada',
        'cantidad_prioridad',
        'cantidad_recibida_aplicada',
        'prioridad_snapshot',
    ];

    protected $casts = [
        'cantidad_solicitada' => 'float',
        'cantidad_prioridad' => 'float',
        'cantidad_recibida_aplicada' => 'float',
        'prioridad_snapshot' => 'array',
    ];

    public function detalleProveedor()
    {
        return $this->belongsTo(OrdenCompraProveedorDetalle::class, 'orden_compra_proveedor_detalle_id');
    }

    public function ordenCompra()
    {
        return $this->belongsTo(Orden_Compra::class, 'orden_compra_id');
    }

    public function ordenCompraDetalle()
    {
        return $this->belongsTo(Orden_Compra_Detalle::class, 'orden_compra_detalle_id');
    }

    public function producto()
    {
        return $this->belongsTo(product::class, 'producto_id');
    }
}
