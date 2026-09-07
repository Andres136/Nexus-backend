<?php

namespace App\Models;

use App\Models\Hseq\SoporteTarea;
use Illuminate\Database\Eloquent\Model;

class Tareas extends Model
{
    protected $fillable = [
      'nombre', 'descripcion', 'fecha_fin',  'departamento_id', 'user_id', 'estado_id', 'fecha_cerrado', 'user_id_creo'
    ];

    //relacion con el modelo User

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //relacion con el usuario que asignó/creó la tarea
    public function creador()
    {
        return $this->belongsTo(User::class, 'user_id_creo');
    }

    public function soportes()
    {
        return $this->hasMany(SoporteTarea::class, 'tarea_id');
    }

    //relacion con el modelo Departamento
    public function departamentos()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    }
}
