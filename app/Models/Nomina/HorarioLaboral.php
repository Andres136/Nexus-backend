<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HorarioLaboral extends Model
{
    use SoftDeletes;

    protected $table = 'horario_laboral';

    protected $fillable = [
        'uuid',
        'hora_ingreso',
        'hora_salida',
        'hora_salida_brake',
        'horara_ingreso_brake',
        'hora_salida_almuerzo',
        'hora_ingreso_almuerzo',
    ];

    protected $casts = [
        'hora_ingreso'          => 'datetime',
        'hora_salida'           => 'datetime',
        'hora_salida_brake'     => 'datetime',
        'horara_ingreso_brake'  => 'datetime',
        'hora_salida_almuerzo'  => 'datetime',
        'hora_ingreso_almuerzo' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}
