<?php

namespace App\Models\Hseq;

use App\Models\RegistroDiario\Novedades;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EficaciaEvaluacion extends Model
{
    protected $table = 'eficacia_evaluaciones';

    protected $fillable = [
        'novedad_id',
        'verificador_id',
        'resultado',
        'observacion',
        'proxima_verificacion',
    ];

    protected $casts = [
        'proxima_verificacion' => 'date',
    ];

    public function novedad()
    {
        return $this->belongsTo(Novedades::class, 'novedad_id');
    }

    public function verificador()
    {
        return $this->belongsTo(User::class, 'verificador_id');
    }

    public function calificaciones()
    {
        return $this->hasMany(EficaciaEvaluacionHallazgo::class, 'eficacia_evaluacion_id');
    }
}
