<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class VehiculoFoto extends Model
{
    protected $table = 'vehiculo_fotos';
    protected $fillable = ['vehiculo_id', 'ruta_foto'];


    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
}
