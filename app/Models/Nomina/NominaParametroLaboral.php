<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class NominaParametroLaboral extends Model
{
    use SoftDeletes;

    protected $table = 'nomina_parametros_laborales';

    protected $fillable = [
        'uuid',
        'anio',
        'fecha_vigencia',
        'salario_minimo',
        'auxilio_transporte',
        'activo',
    ];

    protected $casts = [
        'fecha_vigencia' => 'date',
        'salario_minimo' => 'decimal:2',
        'auxilio_transporte' => 'decimal:2',
        'activo' => 'boolean',
        'uuid' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid ??= Str::uuid();
        });
    }
}
