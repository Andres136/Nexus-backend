<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LiquidacionRetiro extends Model
{
    use SoftDeletes;

    protected $table = 'liquidaciones_retiro';

    protected $fillable = [
        'uuid',
        'user_id',
        'contratacion_id',
        'nomina_id',
        'jornada_laboral_id',
        'fecha_retiro',
        'motivo_retiro',
        'periodo_salario_inicio',
        'periodo_salario_fin',
        'dias_contrato',
        'dias_cesantias',
        'dias_prima',
        'dias_vacaciones_pendientes',
        'base_cesantias',
        'base_prima',
        'base_vacaciones',
        'salario_pendiente',
        'pago_no_prestacional',
        'comisiones_pendientes',
        'cesantias',
        'intereses_cesantias',
        'prima_servicios',
        'vacaciones',
        'indemnizacion',
        'total_devengado',
        'deducciones_nomina',
        'deducciones_comisiones_pendientes',
        'deducciones_finales',
        'total_deducciones',
        'neto_pagar',
        'detalle_calculo',
        'fecha_liquidacion',
    ];

    protected $casts = [
        'fecha_retiro' => 'date',
        'periodo_salario_inicio' => 'date',
        'periodo_salario_fin' => 'date',
        'dias_vacaciones_pendientes' => 'decimal:4',
        'base_cesantias' => 'decimal:2',
        'base_prima' => 'decimal:2',
        'base_vacaciones' => 'decimal:2',
        'salario_pendiente' => 'decimal:2',
        'pago_no_prestacional' => 'decimal:2',
        'comisiones_pendientes' => 'decimal:2',
        'cesantias' => 'decimal:2',
        'intereses_cesantias' => 'decimal:2',
        'prima_servicios' => 'decimal:2',
        'vacaciones' => 'decimal:2',
        'indemnizacion' => 'decimal:2',
        'total_devengado' => 'decimal:2',
        'deducciones_nomina' => 'decimal:2',
        'deducciones_comisiones_pendientes' => 'decimal:2',
        'deducciones_finales' => 'decimal:2',
        'total_deducciones' => 'decimal:2',
        'neto_pagar' => 'decimal:2',
        'detalle_calculo' => 'array',
        'fecha_liquidacion' => 'datetime',
        'uuid' => 'string',
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
        return $this->belongsTo(Contratacion::class, 'contratacion_id')->withTrashed();
    }

    public function nomina()
    {
        return $this->belongsTo(Nomina::class, 'nomina_id')->withTrashed();
    }

    public function jornadaLaboral()
    {
        return $this->belongsTo(JornadaLaboral::class, 'jornada_laboral_id');
    }

    public function comisiones()
    {
        return $this->hasMany(Comision::class, 'liquidacion_retiro_id');
    }
}
