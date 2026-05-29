<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class EncuestaPregunta extends Model
{
    protected $table = 'encuesta_preguntas';

    protected $fillable = [
        'encuesta_id',
        'texto',
        'tipo',
        'opciones',
        'orden',
        'requerida',
    ];

    protected $casts = [
        'opciones'  => 'array',
        'requerida' => 'boolean',
    ];

    public function encuesta()
    {
        return $this->belongsTo(Encuesta::class, 'encuesta_id');
    }

    public function respuestas()
    {
        return $this->hasMany(EncuestaRespuesta::class, 'pregunta_id');
    }
}
