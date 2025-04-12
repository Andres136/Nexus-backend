<?php

namespace App\Models\Crm;

use App\Models\Estados;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;
    protected $table = 'proveedores';
    protected $fillable = [
        'nombre',
        'nit',
        'telefono',
        'correo',
        'direccion',
        'ciudad',
        'estado_id',
        'observaciones'
    ];


      public function estado()
    {
        return $this->belongsTo(Estados::class, 'estado_id');
    }
}
