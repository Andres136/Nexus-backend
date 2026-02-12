<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrdenDetalleObservaciones extends Model
{
    protected $table = 'orden_detalle_observaciones';

    protected $fillable = [
        'orden_detalle_id',
        'observacion',
        'usuario_id',
        'proceso_bolsas_id',
        'proveedor_id',
        'estado',
    ];

    public function ordenDetalle()
    {
        return $this->belongsTo(OrdenCompraProveedorDetalle::class, 'orden_detalle_id', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }
    public function proceso()
    {
        return $this->belongsTo(proceso_bolsas::class, 'proceso_bolsas_id', 'id');
    }
     public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id', 'id');
    }
}
