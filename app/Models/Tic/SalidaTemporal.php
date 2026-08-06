<?php

namespace App\Models\Tic;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SalidaTemporal extends Model
{
    protected $table = 'salidas_temporales';

    protected $fillable = [
        'asignacion_id',
        'usuario_id',
        'motivo',
        'fecha_salida',
        'fecha_retorno_estimada',
        'fecha_retorno_real',
        'observaciones_retorno',
        'estado',
        'creada_por',
    ];

    protected $casts = [
        'fecha_salida' => 'datetime',
        'fecha_retorno_estimada' => 'date',
        'fecha_retorno_real' => 'datetime',
    ];

    public function asignacion()
    {
        return $this->belongsTo(Asignaciones::class, 'asignacion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function creadaPor()
    {
        return $this->belongsTo(User::class, 'creada_por');
    }

    public function actas()
    {
        return $this->hasMany(SalidaTemporalActa::class, 'salida_temporal_id');
    }

    public function actaSalida()
    {
        return $this->hasOne(SalidaTemporalActa::class, 'salida_temporal_id')->where('tipo', 'salida');
    }

    public function actaRetorno()
    {
        return $this->hasOne(SalidaTemporalActa::class, 'salida_temporal_id')->where('tipo', 'retorno');
    }
}
