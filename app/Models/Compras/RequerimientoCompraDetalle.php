<?php

namespace App\Models\Compras;

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
}
