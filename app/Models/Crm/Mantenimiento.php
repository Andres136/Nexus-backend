<?php

namespace App\Models\Crm;

use App\Models\Estados;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $table = 'mantenimientos';
    protected $fillable = [
        'vehiculo_id',
        'programado_por_id',
        'realizado_por_id',
        'tipo_mantenimiento',
        'fecha_programada',
        'fecha_realizado',
        'taller',
        'costo',
        'kilometro_programado',
        'archivo',
        'kilometraje_actual', // Nuevo campo para el kilometraje actual
        'descripcion_trabajo',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function programadoPor()
    {
        return $this->belongsTo(User::class, 'programado_por_id');
    }

    public function realizadoPor()
    {
        return $this->belongsTo(User::class, 'realizado_por_id');
    }
}
