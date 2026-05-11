<?php

namespace App\Models\Nomina;

use App\Models\Hseq\TipoResiduo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Nomina extends Model
{
    use SoftDeletes;

    protected $table = 'nomina';

    protected $fillable = [
        'uuid',
        'user_id',
        'horas_normales_trabajada_id',
        'horas_extras_nocturnas_id',
        'horas_extras_diurna_id',
        'horas_festivas_id',
        'horas_nocturnas_festivas_id',
        'descuento_id',
        'transacional_registros_id',
        'jornada_laboral_id',
    ];

    protected $casts = [
        'horas_normales_trabajada_id'  => 'integer',
        'horas_extras_nocturnas_id'    => 'integer',
        'horas_extras_diurna_id'       => 'integer',
        'horas_festivas_id'            => 'integer',
        'horas_nocturnas_festivas_id'  => 'integer',
        'uuid'                         => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    // ¿A qué empleado pertenece?
    public function empleado()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // ¿Qué descuento tiene?
    public function descuento()
    {
        return $this->belongsTo(Descuento::class, 'descuento_id');
    }

    // ¿De qué registro transaccional viene?
    public function transacionalRegistro()
    {
        return $this->belongsTo(\App\Models\Nomina\TransacionalRegistro::class, 'transacional_registros_id');
    }

    // ¿Qué jornada laboral tiene?
    public function jornadaLaboral()
    {
        return $this->belongsTo(JornadaLaboral::class, 'jornada_laboral_id');
    }

   
}