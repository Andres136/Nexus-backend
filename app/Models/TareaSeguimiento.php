<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaSeguimiento extends Model
{
    protected $table = 'tarea_seguimientos';

    protected $fillable = [
        'tarea_id',
        'user_id',
        'nota',
        'estado_anterior',
        'estado_nuevo',
        'tipo',
    ];

    public function tarea()
    {
        return $this->belongsTo(Tareas::class, 'tarea_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
