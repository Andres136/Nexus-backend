<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CuestionarioRespuesta extends Model
{
    protected $table = 'convocatoria_cuestionario_respuestas';

    protected $fillable = [
        'cuestionario_id',
        'pregunta_id',
        'user_id',
        'valor',
        'enviado_en',
        'calificacion',
        'calificado_por',
        'calificado_en',
    ];

    protected $casts = [
        'calificacion' => 'integer',
        'enviado_en' => 'datetime',
        'calificado_en' => 'datetime',
    ];

    public function cuestionario()
    {
        return $this->belongsTo(Cuestionario::class, 'cuestionario_id');
    }

    public function pregunta()
    {
        return $this->belongsTo(CuestionarioPregunta::class, 'pregunta_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function calificador()
    {
        return $this->belongsTo(User::class, 'calificado_por');
    }
}
