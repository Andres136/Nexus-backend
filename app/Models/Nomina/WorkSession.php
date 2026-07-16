<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WorkSession extends Model
{
    use SoftDeletes;

    protected $table = 'work_sessions';

    protected $fillable = [
        'uuid',
        'user_id',
        'kiosko_id',
        'registro_diario',
        'hora_entrada',
        'foto_respaldo',
        'hora_salida',
        'hora_salida_brake',
        'hora_ingreso_brake',
        'hora_salida_almuerzo',
        'hora_ingreso_almuerzo',
        'minutos_trabajados',
        'minutos_pausa',
        'minutos_almuerzo',
        'minutos_tardanza',
        'sabado_minutos',
        'festivo_minutos',
        'horario_laboral_id',
    ];

    protected $casts = [
        'registro_diario'      => 'date',
        'hora_entrada'         => 'datetime',
        'hora_salida'          => 'datetime',
        'hora_salida_brake'    => 'datetime',
        'hora_ingreso_brake' => 'datetime',
        'hora_salida_almuerzo' => 'datetime',
        'hora_ingreso_almuerzo'=> 'datetime',
        'minutos_trabajados'   => 'integer',
        'minutos_pausa'        => 'integer',
        'minutos_almuerzo'     => 'integer',
        'minutos_tardanza'     => 'integer',
        'sabado_minutos'       => 'decimal:2',
        'festivo_minutos'      => 'decimal:2',
        'uuid'                 => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    // ¿A qué empleado pertenece esta sesión?
    public function empleado()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // ¿En qué kiosko marcó?
    public function kiosko()
    {
        return $this->belongsTo(\App\Models\Nomina\KioskoDevice::class, 'kiosko_id');
    }

    // ¿Qué jornada tenía ese día?
    public function jornadaLaboral()
    {
        return $this->belongsTo(\App\Models\Nomina\JornadaLaboral::class, 'horario_laboral_id');
    }
}
