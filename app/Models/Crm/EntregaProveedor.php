<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class EntregaProveedor extends Model
{
    protected $table = 'entregas_proveedor';

    protected $fillable = [
        'detalle_id',
        'cantidad_entregada',
        'fecha_entrega',
        'observaciones',
        'bodega_id',
        'producto_id',
   
    ];
    // Casts
    protected $casts = [
        'fecha_entrega' => 'datetime',
    ];
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id', 'id');
    }
    public function entrega()
    {
        return $this->belongsTo(OrdenCompraProveedorDetalle::class, 'detalle_id');
    }
    public function detalle()
{
    return $this->belongsTo(OrdenCompraProveedorDetalle::class, 'detalle_id', 'id');
}
    public function procesoBolsas()
    {
        return $this->belongsTo(proceso_bolsas::class, 'proceso_bolsas_id', 'id');
    }

    //relacion con bodega
    public function bodega()
    {
        return $this->belongsTo(bodega::class, 'bodega_id', 'id');
    }
    //relacion con producto
    public function product()
    {
        return $this->belongsTo(product::class, 'product_id', 'id');
    }
}
