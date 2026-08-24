<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImplementacionModulo extends Model
{
    protected $table = 'implementaciones_modulos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_despliegue',
        'activo',
    ];

    protected $casts = [
        'fecha_despliegue' => 'datetime',
        'activo' => 'boolean',
    ];
}
