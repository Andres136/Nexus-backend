<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LiquidacionPrestacion extends Model
{
    protected $table = 'liquidaciones_prestaciones';

    protected $fillable = [
        'uuid',
        'user_id',
        'contratacion_id',
        'vacacion_id',
        'tipo',
        'periodo_inicio',
        'periodo_fin',
        'dias_liquidados',
        'salario_mensual',
        'auxilio_transporte',
        'promedio_variable',
        'base_calculo',
        'valor_calculado',
        'intereses_cesantias',
        'dias_vacaciones',
        'total_liquidado',
        'detalle_calculo',
        'fecha_liquidacion',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
        'dias_liquidados' => 'integer',
        'salario_mensual' => 'decimal:4',
        'auxilio_transporte' => 'decimal:4',
        'promedio_variable' => 'decimal:4',
        'base_calculo' => 'decimal:4',
        'valor_calculado' => 'decimal:2',
        'intereses_cesantias' => 'decimal:2',
        'dias_vacaciones' => 'decimal:4',
        'total_liquidado' => 'decimal:2',
        'detalle_calculo' => 'array',
        'fecha_liquidacion' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contratacion()
    {
        return $this->belongsTo(Contratacion::class, 'contratacion_id');
    }

    public function vacacion()
    {
        return $this->belongsTo(Vacacion::class, 'vacacion_id');
    }
}
