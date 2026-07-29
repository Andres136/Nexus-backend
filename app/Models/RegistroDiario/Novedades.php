<?php

namespace App\Models\RegistroDiario;

use App\Models\Hseq\HallazgoNovedad;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Novedades extends Model
{
    protected $table = 'novedad_diaria';

    protected $fillable = [
        'registro_diario_id',
        'descripcion',
        'numero_no_conformidad',
        'correccion',
        'estado',
        'tipo_accion',
        'fecha_revision',
        'fecha_terminado',
        'soporte',
        'responsable_id',
        'fuentes',
        'causa'
    ];

    public function registroDiario()
    {
        return $this->belongsTo(RegistroDiarios::class, 'registro_diario_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function hallazgos()
    {
        return $this->hasMany(HallazgoNovedad::class, 'novedad_id');
    }
}
