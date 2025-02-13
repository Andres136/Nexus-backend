<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Procesos extends Model
{
    protected $fillable =
     ['nombre','user_id', 'departamento_id'];

    // funcion para relacionar con la tabla departamentos
    public function departamentos()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    }

    // funcion para relacionar procesos con  documentos
    public function documentos()
    {
        return $this->hasMany(Documentos::class, 'proceso_id');
    }
    // funcion para relacionar procesos con usuarios
    public function usuarios()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    // funcion para relacionar procesos con errores

  
}
