<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class DocumentoVehiculo extends Model
{
    //
    protected $table = 'documento_vehiculos';
    protected $fillable = [
        'vehiculo_id',
        'tipo_documento',
        'fecha_vencimiento',
        'fecha_renovacion',
        'documento_pdf',
        'estado',
    ];
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
} 
