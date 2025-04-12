<?php

namespace App\Models\Crm;

use App\Models\Estados;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $table = 'mantenimientos';
    protected $fillable = [
        'vehiculo_id',
        'tipo_mantenimiento',
        'fecha_programada',
        'fecha_realizado',
        'taller',
        'costo',
        'kilometro_programado',
        'archivo',
        'descripcion_trabajo',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
  
}
