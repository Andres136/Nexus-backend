<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class EncuestaRespuesta extends Model
{
    protected $table = 'encuesta_respuestas';

    protected $fillable = [
        'envio_id',
        'pregunta_id',
        'valor',
    ];

    public function envio()
    {
        return $this->belongsTo(EncuestaEnvio::class, 'envio_id');
    }

    public function pregunta()
    {
        return $this->belongsTo(EncuestaPregunta::class, 'pregunta_id');
    }
}
