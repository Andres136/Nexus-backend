<?php

namespace App\Models\Traslados;

use App\Models\Crm\empresa;
use App\Models\Crm\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Envio_internos extends Model
{
    protected $table = 'envios_internos';

    protected $fillable = [
        'sede_origen_id',
        'sede_destino_id',
        'usuario_id',
        'responsable_id',
        'estado_id',
        'fecha_envio',
        'fecha_recepcion',
        'notas',
        'empresa_id'
    ];

    protected $casts = [
        'fecha_envio' => 'date',
        'fecha_recepcion' => 'datetime',
    ];

    // Relaciones
    public function detalles()
    {
        return $this->hasMany(Detalles_envio_internos::class, 'envio_interno_id');
    }

    //Relacion con sede origen
    public function sedeOrigen()
    {
        return $this->belongsTo(Sede::class, 'sede_origen_id');
    }
    //Relacion con sede destino
    public function sedeDestino()
    {
        return $this->belongsTo(Sede::class, 'sede_destino_id');
    }
    //Relacion con usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    //Relacion con el responsable que recibe el traslado
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
    //relacion  con empresa
    public function empresa()
    {
        return $this->belongsTo(empresa::class, 'empresa_id');
    }
   
    

}
