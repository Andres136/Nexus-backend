<?php

namespace App\Models\Traslados;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Responsabilidad extends Model
{


    const INVENTARIO = 6;
    const BODEGA = 7;

    protected $table = 'responsabilidades';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    
    /**
     * Usuarios asociados a la responsabilidad.
     */
 public function usuarios()
{
    return $this->belongsToMany(
        User::class,
        'responsabilidades_user', // confirma nombre real
        'responsabilidad_id',         // FK en la pivote
        'user_id'                     // FK en la pivote
    )
    ->withPivot([
        'id',
        'bodega_id',
        'sede_id',
        'activo',
        'fecha_asignacion',
        'fecha_fin',
    ])
    ->withTimestamps();
}

}
