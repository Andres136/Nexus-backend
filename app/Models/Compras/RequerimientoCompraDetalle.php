<?php

namespace App\Models\Compras;

use App\Models\Crm\OrdenCompraProveedorDetalle;
use App\Models\Crm\product;
use App\Models\Crm\Proveedor;
use Illuminate\Database\Eloquent\Model;

class RequerimientoCompraDetalle extends Model
{
    protected $table = 'requerimiento_compra_detalles';

    protected $fillable = [
        'requerimiento_compra_id',
        'producto_id',
        'cantidad_solicitada',
        'cantidad_aprobada',
        'cantidad_comprada',
        'costo_estimado',
        'proveedor_sugerido_id',
        'referencia_sugerida',
        'observacion',
        'orden_compra_proveedor_detalle_id',
    ];

    protected $casts = [
        'cantidad_solicitada' => 'float',
        'cantidad_aprobada' => 'float',
        'cantidad_comprada' => 'float',
        'costo_estimado' => 'float',
    ];

    public function requerimiento()
    {
        return $this->belongsTo(RequerimientoCompra::class, 'requerimiento_compra_id');
    }

    public function producto()
    {
        return $this->belongsTo(product::class, 'producto_id');
    }

    public function proveedorSugerido()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_sugerido_id');
    }

    // OC (a proveedor) que ya cubre este item puntual. Nula mientras el item
    // sigue pendiente de compra; permite que un mismo requerimiento se cubra
    // con varias OC (una por proveedor) sin perder trazabilidad por item.
    public function ordenCompraProveedorDetalle()
    {
        return $this->belongsTo(OrdenCompraProveedorDetalle::class, 'orden_compra_proveedor_detalle_id');
    }
}
