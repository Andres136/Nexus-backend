<?php

namespace App\Models\Productividad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CategoriaActividad extends Model
{
    protected $table = 'categorias_actividad';

    protected $fillable = [
        'uuid',
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });
    }
}
