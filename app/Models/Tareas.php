<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tareas extends Model
{
    protected $fillable = [
      'nombre', 'descripcion', 'fecha_fin',  'departamento_id', 'user_id', 'estado_id'
    ];

    //relacion con el modelo User

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //relacion con el modelo Departamento
    public function departamentos()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    }
}
