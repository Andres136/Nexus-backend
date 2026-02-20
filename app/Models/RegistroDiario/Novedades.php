<?php

namespace App\Models\RegistroDiario;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Novedades extends Model
{
    protected $table = 'novedad_diaria';

    protected $fillable = [
        'registro_diario_id',
        'descripcion',
        'estado',
        'fecha_revision',
        'fecha_terminado',
        'soporte',
        'responsable_id',
    ];

    public function registroDiario()
    {
        return $this->belongsTo(RegistroDiarios::class, 'registro_diario_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
