<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamentos extends Model
{
    protected $fillable =
     ['nombre', 'descripcion', 'macroprocesos_id'];

   // funcion para relacionar con la tabla macroprocesos
    public function macroprocesos()
    {
        return $this->belongsTo(Macroprocesos::class, 'macroprocesos_id');
    } 

    //funcion para relacionar procesos con departamentos
    public function procesos()
    {
        return $this->hasMany(Procesos::class, 'departamento_id');
    }
}
