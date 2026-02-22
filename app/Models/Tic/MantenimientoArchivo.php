<?php

namespace App\Models\Tic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MantenimientoArchivo extends Model
{
    protected $table = 'mantenimiento_archivos';

    protected $appends = ['url'];
    protected $fillable = [
      'mantenimiento_id',
    'archivo',
    'tipo',
    'descripcion'
    ];


public function getUrlAttribute()
{
    return Storage::disk('public')->url($this->archivo);
}

    public function mantenimiento()
    {
        return $this->belongsTo(MantenimientoEquipos::class, 'mantenimiento_id');
    }
}
