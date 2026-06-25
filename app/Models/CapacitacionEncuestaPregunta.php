<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapacitacionEncuestaPregunta extends Model
{
    protected $table = 'capacitacion_encuesta_preguntas';

    protected $fillable = [
        'encuesta_id',
        'texto',
        'tipo',
        'opciones',
        'orden',
        'requerida',
        'max_escala',
        'respuesta_correcta',
    ];

    protected $casts = [
        'opciones' => 'array',
        'requerida' => 'boolean',
        'max_escala' => 'integer',
    ];

    public function encuesta()
    {
        return $this->belongsTo(CapacitacionEncuesta::class, 'encuesta_id');
    }

    public function respuestas()
    {
        return $this->hasMany(CapacitacionEncuestaRespuesta::class, 'pregunta_id');
    }
}
