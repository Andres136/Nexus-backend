<?php

namespace App\Models\Hseq;

use Illuminate\Database\Eloquent\Model;

class EficaciaEvaluacionHallazgo extends Model
{
    protected $table = 'eficacia_evaluacion_hallazgos';

    protected $fillable = [
        'eficacia_evaluacion_id',
        'hallazgo_id',
        'calificacion',
    ];

    public function evaluacion()
    {
        return $this->belongsTo(EficaciaEvaluacion::class, 'eficacia_evaluacion_id');
    }

    public function hallazgo()
    {
        return $this->belongsTo(HallazgoNovedad::class, 'hallazgo_id');
    }
}
