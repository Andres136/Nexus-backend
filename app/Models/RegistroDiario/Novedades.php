<?php

namespace App\Models\RegistroDiario;

use Illuminate\Database\Eloquent\Model;

class Novedades extends Model
{
    protected $table = 'novedad_diaria';

    protected $fillable = [
        'registro_diario_id',
        'descripcion',
    ];

    public function registroDiario()
    {
        return $this->belongsTo(RegistroDiarios::class, 'registro_diario_id');
    }
}
