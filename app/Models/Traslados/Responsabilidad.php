<?php

namespace App\Models\Traslados;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Responsabilidad extends Model
{


    const INVENTARIO = 1;
    const BODEGA = 3;

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
        return $this->belongsToMany(User::class, 'responsabilidades_user')
        ->withPivot([
            'responsable_id',
            'bodega_id',
            'sede_id',
            'activo',
            'fecha_asignacion',
            'fecha_fin',
        ])
        ->withTimestamps();
    }
}
