<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HorarioUsuarioSemanal extends Model
{
    use SoftDeletes;

    protected $table = 'horarios_usuario_semanales';

    protected $fillable = [
        'uuid',
        'user_id',
        'dia_semana',
        'jornada_laboral_id',
        'hora_entrada',
        'hora_entrada_limite',
        'hora_salida_pausa',
        'hora_ingreso_pausa',
        'hora_salida_almuerzo',
        'hora_ingreso_almuerzo',
        'hora_salida',
        'duracion_pausa_minutos',
        'duracion_almuerzo_minutos',
        'status',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'duracion_pausa_minutos' => 'integer',
        'duracion_almuerzo_minutos' => 'integer',
        'status' => 'boolean',
        'uuid' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jornadaLaboral()
    {
        return $this->belongsTo(JornadaLaboral::class, 'jornada_laboral_id');
    }
}
