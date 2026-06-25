<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapacitacionEncuestaRespuesta extends Model
{
    protected $table = 'capacitacion_encuesta_respuestas';

    protected $fillable = [
        'envio_id',
        'pregunta_id',
        'valor',
    ];

    public function envio()
    {
        return $this->belongsTo(CapacitacionEncuestaEnvio::class, 'envio_id');
    }

    public function pregunta()
    {
        return $this->belongsTo(CapacitacionEncuestaPregunta::class, 'pregunta_id');
    }
}
