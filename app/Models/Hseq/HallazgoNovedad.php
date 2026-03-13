<?php

namespace App\Models\Hseq;

use App\Models\RegistroDiario\Novedades;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HallazgoNovedad extends Model
{
    protected $table = "hallazgo_novedades";
    protected $fillable = [
        'novedad_id',
        'causa',
        'plan_accion',
        'responsable_id',
        'fecha_cierre',
        'fecha_revision',
        'estado',
        'observaciones',
    ];

    public function novedad()
    {
        return $this->belongsTo(Novedades::class, 'novedad_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
