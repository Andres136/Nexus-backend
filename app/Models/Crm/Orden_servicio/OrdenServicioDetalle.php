<?php

namespace App\Models\Crm\Orden_servicio;

use App\Models\Crm\OrdenCompraProveedorDetalle;
use Illuminate\Database\Eloquent\Model;

class OrdenServicioDetalle extends Model
{
    protected $table = 'orden_servicio_detalles';
    protected $fillable = [
      'orden_servicio_id',
      'orden_compra_detalle_id',
        'cantidad',
    ];


    //Relación con orden de servicio
    public function ordenServicio()
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    //Relación con orden de compra detalle
    public function ordenCompraDetalle()
    {
        return $this->belongsTo(OrdenCompraProveedorDetalle::class, 'orden_compra_detalle_id');
    }
}
