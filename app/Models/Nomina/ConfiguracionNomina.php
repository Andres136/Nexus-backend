<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ConfiguracionNomina extends Model
{
    use SoftDeletes;

    protected $table = 'configuracion_nomina';

    protected $fillable = [
        'uuid',
        'nombre',
        'porcentaje_salud_empleado',
        'porcentaje_pension_empleado',
        'firma_talento_humano',
        'status',
    ];

    protected $casts = [
        'porcentaje_salud_empleado' => 'decimal:2',
        'porcentaje_pension_empleado' => 'decimal:2',
        'status' => 'boolean',
        'uuid' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}
