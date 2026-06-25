<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapacitacionEncuestaEnvio extends Model
{
    protected $table = 'capacitacion_encuesta_envios';

    protected $fillable = [
        'encuesta_id',
        'user_id',
        'enviado_por',
        'token',
        'estado',
        'sent_at',
        'responded_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function encuesta()
    {
        return $this->belongsTo(CapacitacionEncuesta::class, 'encuesta_id')->with('preguntas');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function remitente()
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    public function respuestas()
    {
        return $this->hasMany(CapacitacionEncuestaRespuesta::class, 'envio_id');
    }
}
