<?php

namespace App\Models\Traslados;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Responsabilidad extends Model
{


    const INVENTARIO = 6;
    const BODEGA = 7;

    protected $table = 'responsabilidades';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
    ];

    protected static function booted(): void
    {
        // `codigo` es el identificador estable que usa el código para resolver
        // "quién tiene esta responsabilidad" (ver Responsabilidad::where('codigo', ...)
        // en los listeners de Traslados y en ProductoNoConformeService). Se fija una
        // sola vez al crear, así renombrar `nombre` después no rompe nada.
        static::creating(function (self $responsabilidad) {
            if (empty($responsabilidad->codigo)) {
                $responsabilidad->codigo = Str::slug($responsabilidad->nombre, '_');
            }
        });
    }


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
