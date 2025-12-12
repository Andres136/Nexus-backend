<?php

namespace App\Models\Vsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AlistamientoUsuario extends Pivot
{
    protected $table = 'alistamiento_usuario';
    protected $fillable = [
        'alistamiento_id',
        'usuario_id',
        'estado',
        'inicio',
        'pausado_en',
        'tiempo_segundos',
        'razon',
    ];
    public $timestamps = true;
}
