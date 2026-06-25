<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class OrdenCompraProveedorDetalle extends Model
{
    protected $table = 'orden_compra_proveedor_detalles';
    protected $fillable = [
        'orden_id',
        'code',
        'descripcion',
        'cantidad_solicitada',
        'cantidad_entregada',
        'item',
        'proceso_bolsas_id',
        'proveedor_id',
        'producto_id',
    ];
    protected $casts = [
        'cantidad_solicitada' => 'float',
        'cantidad_entregada' => 'float',
    ];

    //Relacion inversa con el modelo OrdenCompraProveedor
    public function orden()
    {
        return $this->belongsTo(OrdenCompraProveedor::class, 'orden_id');
    }
    //Relacion con el modelo EntregaProveedor
    public function entregas()
    {
        return $this->hasMany(EntregaProveedor::class, 'detalle_id');
    }
    //Relacion con el modelo ProcesoBolsas
    public function procesoBolsas()
    {
        return $this->belongsTo(proceso_bolsas::class, 'proceso_bolsas_id', 'id');
    }
    //Relacion con el modelo Proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id', 'id');
    }
    //Relacion con el modelo Producto
    public function producto()
    {
        return $this->belongsTo(product::class, 'producto_id', 'id');
    }

    public function procesoBolsa()
    {
        return $this->belongsTo(proceso_bolsas::class, 'proceso_bolsas_id', 'id');
    }

    public function observaciones()
    {
        return $this->hasMany(OrdenDetalleObservaciones::class, 'orden_detalle_id', 'id');
    }

    public function origenes()
    {
        return $this->hasMany(OrdenCompraProveedorDetalleOrigen::class, 'orden_compra_proveedor_detalle_id');
    }
}
