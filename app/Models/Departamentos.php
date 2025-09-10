<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamentos extends Model
{
    protected $fillable =
     ['nombre', 'descripcion', 'macroprocesos_id', 'icono', 'responsable_id'];


     public function getIconoAttribute($value)
     {
         return url('storage/'.$value);
     }
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
    //funcion para relacionar departamentos con usuarios
    public function users()
    {
        return $this->hasMany(User::class, 'departamento_id');
    }

    //funcion para relacionar departamentos con errores
    public function errores()
    {
        return $this->hasMany(Errores::class, 'departamento_id');
    }

    //funcion para relacionar departamentos con tareas  
    public function tareas()
    {
        return $this->hasMany(Tareas::class, 'departamento_id');
    }
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
    
}
