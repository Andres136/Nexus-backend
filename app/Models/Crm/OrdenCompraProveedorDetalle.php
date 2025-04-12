<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class OrdenCompraProveedorDetalle extends Model
{
    protected $table = 'orden_compra_proveedor_detalles';
    protected $fillable = [
        'orden_id',
        'descripcion',
        'cantidad_solicitada',
        'cantidad_entregada',
        'item',
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
}
