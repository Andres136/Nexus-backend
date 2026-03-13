<?php

namespace App\Models\Hseq;

use Illuminate\Database\Eloquent\Model;

class PreguntaInspeccion extends Model
{
    protected $table = 'preguntas_inspecciones';

    protected $fillable = [
        'tipo_inspeccion_id',
        'pregunta',
        'tipo_respuesta',
        'activa',
        'orden'
    ];


    public function tipoInspeccion()
    {
        return $this->belongsTo(TipoInspeccion::class, 'tipo_inspeccion_id');
    }
}
