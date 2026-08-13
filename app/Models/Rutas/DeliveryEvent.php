<?php

namespace App\Models\Rutas;

use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Crm\Proveedor;
use App\Models\Crm\Orden_servicio\OrdenServicio;
use App\Models\Crm\Vehiculo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DeliveryEvent extends Model
{
    protected $table = 'delivery_events';

    protected $fillable = [
        'tipo',
        'orden_id',
        'proveedor_id',
        'orden_servicio_id',
        'fecha_entrega',
        'hora',
        'usuario_id',
        'vehiculo_id',
        'cantidad',
        'observaciones',
        'estado',
    ];


    //Relacion con orden de trabajo
    public function orden()
    {
        return $this->belongsTo(Orden_Compra::class, 'orden_id', 'id');
    }

    // Relación con el proveedor (tipo = recogida)
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function ordenServicio()
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    // Órdenes de compra a proveedor adjuntas a esta recogida
    public function ordenesCompraProveedor()
    {
        return $this->belongsToMany(
            OrdenCompraProveedor::class,
            'delivery_event_orden_compra_proveedor',
            'delivery_event_id',
            'orden_compra_proveedor_id'
        );
    }
    //Relacion con usuarios
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // Relación con vehículos
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    // Relación con registros de entrega
    public function records()
    {
        return $this->hasMany(DeliveryRecord::class, 'delivery_event_id');
    }

   // Último registro (estado real)
    public function lastRecord()
    {
        return $this->hasOne(DeliveryRecord::class)
            ->latestOfMany();
    }

    public function getClienteFinalAttribute()
{
    return optional(
        optional($this->orden)->ordenTrabajo
    )->cliente;
}

public function getOrdenTrabajoIdAttribute()
{
    return optional(optional($this->orden)->ordenTrabajo)->id;
}
protected $appends = ['cliente_final', 'orden_trabajo_id'];


}
