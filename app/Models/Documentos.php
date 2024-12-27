<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documentos extends Model
{
    protected $fillable =
     ['nombre', 'documento', 'user_id', 'proceso_id', 'version'];

    // funcion para relacionar con la tabla procesos
    public function procesos()
    {
        return $this->belongsTo(Procesos::class, 'proceso_id');
    }

    // funcion para relacionar documentos con usuarios
    public function usuarios()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
