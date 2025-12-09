<?php

namespace App\Models\Crm;
use App\Models\Crm\Orden_Compra_Detalle;
use App\Models\Rutas\DeliveryEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrdenTrabajoEntrega extends Model
{
    protected $table = 'orden_trabajo_entregas';

    protected $fillable = [
        'orden_trabajo_id',
        'detalle_id',
        'cantidad',
        'faltante',
        'fecha_entrega',
        'usuario_id',
        'observaciones',
    ];
//Relacion con la tabla orden_de_trabajos
public function ordenTrabajo()
{
    return $this->belongsTo(\App\Models\Crm\OrdenDeTrabajo::class, 'orden_trabajo_id', 'id');
}

public function detalle()
{
    return $this->belongsTo(\App\Models\Crm\Orden_Compra_Detalle::class, 'detalle_id', 'id');
}

public function usuario()
{
    return $this->belongsTo(\App\Models\User::class, 'usuario_id', 'id');
}

}
