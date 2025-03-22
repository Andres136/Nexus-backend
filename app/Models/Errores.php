<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Errores extends Model
{
    protected $fillable =[
        'descripcion', 
        'departamento_id'
    ];


    public function procesos()
    {
        return $this->belongsTo(Procesos::class, 'proceso_id');
    }
    // Relacion con departamentos
    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    } 
}
