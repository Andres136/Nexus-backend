<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;

class CuestionarioPregunta extends Model
{
    protected $table = 'convocatoria_cuestionario_preguntas';

    protected $fillable = [
        'cuestionario_id',
        'texto',
        'tipo',
        'opciones',
        'orden',
    ];

    protected $casts = [
        'opciones' => 'array',
    ];

    public function cuestionario()
    {
        return $this->belongsTo(Cuestionario::class, 'cuestionario_id');
    }

    public function respuestas()
    {
        return $this->hasMany(CuestionarioRespuesta::class, 'pregunta_id');
    }
}
