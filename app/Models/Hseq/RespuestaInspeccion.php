<?php

namespace App\Models\Hseq;

use Illuminate\Database\Eloquent\Model;

class RespuestaInspeccion extends Model
{
    protected $table = "respuesta_inspecciones";
    protected $fillable = [
        'inspeccion_id',
        'pregunta_inspeccion_id',
        'respuesta',
        'observaciones',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(InspeccionHseq::class, 'inspeccion_id');
    }
    public function pregunta()
    {
        return $this->belongsTo(PreguntaInspeccion::class, 'pregunta_inspeccion_id');
    }
}
