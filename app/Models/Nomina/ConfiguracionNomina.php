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
        'recargo_extra_diurna',
        'recargo_extra_nocturna',
        'recargo_festiva',
        'recargo_nocturna_festiva',
        'porcentaje_incapacidad',
        'hora_inicio_nocturna',
        'hora_fin_nocturna',
        'firma_talento_humano',
        'status',
    ];

    protected $casts = [
        'porcentaje_salud_empleado' => 'decimal:2',
        'porcentaje_pension_empleado' => 'decimal:2',
        'recargo_extra_diurna' => 'decimal:4',
        'recargo_extra_nocturna' => 'decimal:4',
        'recargo_festiva' => 'decimal:4',
        'recargo_nocturna_festiva' => 'decimal:4',
        'porcentaje_incapacidad' => 'decimal:4',
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
