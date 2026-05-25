<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HorarioOperacionDiaria extends Model
{
    use SoftDeletes;

    protected $table = 'horario_operacion_diarias';

    protected $fillable = [
        'uuid',
        'fecha',
        'jornada_laboral_id',
        'hora_salida_pausa',
        'hora_ingreso_pausa',
        'hora_salida_almuerzo',
        'hora_ingreso_almuerzo',
        'duracion_pausa_minutos',
        'duracion_almuerzo_minutos',
        'motivo',
        'status',
    ];

    protected $casts = [
        'fecha' => 'date',
        'duracion_pausa_minutos' => 'integer',
        'duracion_almuerzo_minutos' => 'integer',
        'status' => 'boolean',
        'uuid' => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function jornadaLaboral()
    {
        return $this->belongsTo(JornadaLaboral::class, 'jornada_laboral_id');
    }
}
