<?php

namespace App\Models\Rutas;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DeliveryRecord extends Model
{
    protected $table = 'delivery_records';

    protected $fillable = [
        'delivery_event_id',
        'fecha_real',
        'hora_real',
        'cantidad_entregada',
        'resultado',
        'motivo_no_entrega',
        'observaciones',
        'usuario_id',
    ];

       protected $casts = [
        'fecha_real' => 'date',
        'hora_real' => 'datetime:H:i',
        'cantidad_entregada' => 'decimal:2',
    ];

    /* =====================
       RELACIONES
    ====================== */

    public function event()
    {
        return $this->belongsTo(DeliveryEvent::class, 'delivery_event_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
