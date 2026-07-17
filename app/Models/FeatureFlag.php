<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $table = 'feature_flags';

    protected $fillable = [
        'clave',
        'activo',
        'usuarios_piloto',
        'descripcion',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'usuarios_piloto' => 'array',
    ];

    public static function habilitadaPara(string $clave, int $userId): bool
    {
        $flag = static::where('clave', $clave)->first();

        if (! $flag || ! $flag->activo) {
            return false;
        }

        if (empty($flag->usuarios_piloto)) {
            return true;
        }

        return in_array($userId, $flag->usuarios_piloto, true);
    }
}
