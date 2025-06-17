<?php

namespace App\Models\Crm;

use App\Models\Estados;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';
    protected $fillable = [
       
        'placa',
        'marca',
        'modelo',
        'tipo',
        'anio',
        'kilometraje_actual',
        'estado',
        'observaciones',
        'foto',
        'licencia_transito',
        'conductor',

       
    ];
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'vehiculo_id');
    }
    public function inspecciones()
    {
        return $this->hasMany(Inspeccion::class, 'vehiculo_id');
    }
    public function documentos()
    {
        return $this->hasMany(DocumentoVehiculo::class, 'vehiculo_id');
    }
  
    public function fotos()
    {
        return $this->hasMany(VehiculoFoto::class, 'vehiculo_id');
    }
}
