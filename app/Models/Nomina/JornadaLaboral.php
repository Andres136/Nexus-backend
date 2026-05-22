<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class JornadaLaboral extends Model
{
    use SoftDeletes;

    protected $table = 'jornada_laborals';

    protected $fillable = [
        'uuid',
        'nombre',
        'horas_semanales',
        'status',
        'hora_entrada',
        'hora_salida_almuerzo',
        'hora_ingreso_almuerzo',
        'hora_salida_pausa',
        'hora_ingreso_pausa',
        'hora_salida',
        'duracion_pausa_minutos',
        'duracion_almuerzo_minutos',
        'comando_voz_activo',
    ];

    protected $casts = [
        'status'                    => 'boolean',
        'horas_semanales'           => 'integer',
        'duracion_pausa_minutos'    => 'integer',
        'duracion_almuerzo_minutos' => 'integer',
        'comando_voz_activo'        => 'boolean',
        'uuid'                      => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}
