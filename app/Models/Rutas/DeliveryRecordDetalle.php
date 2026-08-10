<?php

namespace App\Models\Rutas;

use App\Models\Crm\OrdenCompraProveedorDetalle;
use Illuminate\Database\Eloquent\Model;

class DeliveryRecordDetalle extends Model
{
    protected $table = 'delivery_record_detalles';

    protected $fillable = [
        'delivery_record_id',
        'orden_compra_proveedor_detalle_id',
        'cantidad_recogida',
    ];

    protected $casts = [
        'cantidad_recogida' => 'float',
    ];

    public function record()
    {
        return $this->belongsTo(DeliveryRecord::class, 'delivery_record_id');
    }

    public function detalle()
    {
        return $this->belongsTo(OrdenCompraProveedorDetalle::class, 'orden_compra_proveedor_detalle_id');
    }
}
