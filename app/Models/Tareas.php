<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tareas extends Model
{
    protected $fillable = [
      'nombre', 'descripcion', 'fecha_fin',  'proceso_id', 'user_id', 'estado_id'
    ];
}
