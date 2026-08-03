<?php

namespace App\Models\Hseq;

use App\Models\Tareas;
use Illuminate\Database\Eloquent\Model;

class SoporteTarea extends Model
{
    protected $table = 'soporte_tareas';

    protected $fillable = [
        'soporte_tarea',
        'nombre_original',
        'tarea_id',
        'hallazgo_id',
    ];

    public function tarea()
    {
        return $this->belongsTo(Tareas::class, 'tarea_id');
    }

    public function hallazgo()
    {
        return $this->belongsTo(HallazgoNovedad::class, 'hallazgo_id');
    }
}
