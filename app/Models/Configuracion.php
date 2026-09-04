<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Banderas de configuración global (llave-valor). Usar los helpers estáticos
 * en vez de consultar la tabla directamente.
 */
class Configuracion extends Model
{
    protected $table = 'configuraciones';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['clave', 'valor'];

    protected $casts = ['valor' => 'array'];

    public static function obtener(string $clave, mixed $porDefecto = null): mixed
    {
        return static::query()->find($clave)?->valor ?? $porDefecto;
    }

    public static function guardar(string $clave, mixed $valor): void
    {
        static::query()->updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }
}
