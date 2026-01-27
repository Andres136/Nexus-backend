<?php

namespace App\Models\Traslados;

use App\Models\Crm\bodega;
use App\Models\Estados;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Traslado_Bodega extends Model
{
    protected $table = 'traslados_bodega';

    protected $fillable = [
        'codigo',
        'bodega_origen_id',
        'bodega_destino_id',
        'usuario_aprobador_bodega_id',
        'usuario_aprobador_inventario_id',
        'usuario_creador_id',
        'estado',
        'fecha_despacho',
        'fecha_recepcion',
        'observaciones',
    ];


    /*
    *
    Relaciones
    **/
protected $casts = [
        'fecha_despacho' => 'datetime',
        'fecha_recepcion' => 'datetime',
    ];

   public function detalles()
   {
       return $this->hasMany(TrasladoBodegaDetalle::class, 'traslado_bodega_id');
   }

    public function bodegaOrigen()
    {
        return $this->belongsTo(bodega::class, 'bodega_origen_id');
    }

    public function bodegaDestino()
    {
        return $this->belongsTo(bodega::class, 'bodega_destino_id');
    }

    public function   creador()
    {
        return $this->belongsTo(User::class, 'usuario_creador_id');
    }

    public function usuarioAprobador()
    {
        return $this->belongsTo(User::class, 'usuario_aprobador_bodega_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estados::class);
    }
    public function aprobadorBodega()
{
    return $this->belongsTo(User::class, 'usuario_aprobador_bodega_id');
}

public function aprobadorInventario()
{
    return $this->belongsTo(User::class, 'usuario_aprobador_inventario_id');
}


}
