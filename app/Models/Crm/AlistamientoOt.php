<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AlistamientoOt extends Model
{
    protected $table = 'alistamientos_ot';

    protected $fillable = [
        'orden_trabajo_id',
        'orden_compra_detalle_id',
        'producto_id',
        'bodega_id',
        'cantidad',
        'tipo',
        'usuario_id',
        'fecha_alistamiento',
    ];

    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenDeTrabajo::class, 'orden_trabajo_id');
    }

    public function ordenCompraDetalle()
    {
        return $this->belongsTo(Orden_Compra_Detalle::class, 'orden_compra_detalle_id');
    }

    public function producto()
    {
        return $this->belongsTo(product::class, 'producto_id');
    }

    public function bodega()
    {
        return $this->belongsTo(bodega::class, 'bodega_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
