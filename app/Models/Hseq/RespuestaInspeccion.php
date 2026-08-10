<?php

namespace App\Models\Hseq;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RespuestaInspeccion extends Model
{
    protected $table = "respuesta_inspecciones";
    protected $fillable = [
        'inspeccion_id',
        'pregunta_inspeccion_id',
        'respuesta',
        'observaciones',
        'foto_cierre',
        'observaciones_cierre',
        'cerrado_en',
        'cerrado_por',
    ];

    protected $casts = [
        'cerrado_en' => 'datetime',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(InspeccionHseq::class, 'inspeccion_id');
    }
    public function pregunta()
    {
        return $this->belongsTo(PreguntaInspeccion::class, 'pregunta_inspeccion_id');
    }
    public function cerradoPor()
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }
}
