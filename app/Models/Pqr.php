<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pqr extends Model
{
    protected $fillable = [
        'nombre',
        'empresa',
        'email',
        'telefono',
        'mensaje',
        'respuesta', // Nueva columna añadida en la migración
        'estado_id',
        // Nuevas columnas añadidas en la migración
        'codigo_radicado',
        'archivo',
        'asignado_a',

    ];

    public function estado()
    {
        return $this->belongsTo(Estados::class);
    }
    public function asignado()
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }
    

}
