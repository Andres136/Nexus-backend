<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrdenTrabajoEntregaCorreccion extends Model
{
    protected $table = 'orden_trabajo_entrega_correcciones';

    protected $fillable = [
        'orden_trabajo_id',
        'detalle_id',
        'cantidad_anterior',
        'cantidad_nueva',
        'motivo',
        'usuario_id',
    ];

    protected $casts = [
        'cantidad_anterior' => 'decimal:2',
        'cantidad_nueva' => 'decimal:2',
    ];

    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenDeTrabajo::class, 'orden_trabajo_id');
    }

    public function detalle()
    {
        return $this->belongsTo(Orden_Compra_Detalle::class, 'detalle_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
