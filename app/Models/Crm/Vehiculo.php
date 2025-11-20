<?php

namespace App\Models\Crm;

use App\Models\Estados;
use App\Models\Rutas\DeliveryEvent;
use App\Models\User;
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
        'nombre', // Nuevo campo para el nombre del vehículo
        'tipo_servicio', // Nuevo campo para el tipo de servicio
        'color', // Nuevo campo para el color del vehículo
        'tipo_carroceria', // Nuevo campo para el tipo de carrocería
        'tipo_combustible', // Nuevo campo para el tipo de combustible
        'numero_motor', // Nuevo campo para el número de motor
        'numero_chasis', // Nuevo campo para el número de chasis
        'propietario', // Nuevo campo para el propietario del vehículo
        'identificacion', // Nuevo campo para la identificación del propietario
        'organismo_transito', // Nuevo campo para el organismo de tránsito
        'fecha_matricula', // Nuevo campo para la fecha de matrícula

       
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
        return $this->hasMany(VehiculoFoto::class);
    }
public function conductor()
{
    return $this->belongsTo(User::class, 'conductor'); // Suponiendo que el conductor es un usuario
}


// Relación con los eventos de entrega
    public function deliveryEvents()
    {
        return $this->hasMany(DeliveryEvent::class, 'vehiculo_id');
    }

}
