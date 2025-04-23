<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class Inspeccion extends Model
{
    //
    protected $table = 'inspeccions';
    protected $fillable = [
        'vehiculo_id',
        'fecha',
        'responsable',
        'estado_general',
        'observaciones',
        'documento',    
      
    ];
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
}
