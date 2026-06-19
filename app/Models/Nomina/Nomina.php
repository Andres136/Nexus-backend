<?php

namespace App\Models\Nomina;

use App\Models\User;
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
        'jornada_laboral_id',
        'contratacion_id',
        'preliquidacion_id',
        'descuento_id',
        'transacional_registros_id',

        // Período
        'periodo_inicio',
        'periodo_fin',

        // Horas trabajadas por tipo
        'horas_normales',
        'horas_extras_nocturnas',
        'horas_extras_diurnas',
        'horas_festivas',
        'horas_nocturnas_festivas',

        // Snapshot de tarifas al momento de liquidar
        'valor_hora_normal',
        'valor_hora_nocturna',
        'valor_hora_dominical',
        'valor_hora_dominical_extra',

        // Devengados
        'salario_base_devengado',
        'auxilio_transporte',
        'total_comisiones',
        'total_novedades_retroactivas',
        'detalle_novedades_retroactivas',
        'valor_horas_normales',
        'valor_horas_extras_nocturnas',
        'valor_horas_extras_diurnas',
        'valor_horas_festivas',
        'valor_horas_nocturnas_festivas',
        'total_devengado',

        // Deducciones
        'deduccion_salud',
        'deduccion_pension',
        'total_descuentos_adicionales',
        'total_deducciones',

        // Costo empresa / aportes empleador
        'base_aportes_empleador',
        'porcentaje_salud_empleador',
        'porcentaje_pension_empleador',
        'porcentaje_arl',
        'porcentaje_sena',
        'porcentaje_icbf',
        'porcentaje_caja_compensacion',
        'costo_salud_empleador',
        'costo_pension_empleador',
        'costo_arl',
        'costo_sena',
        'costo_icbf',
        'costo_caja_compensacion',
        'costo_parafiscales',
        'costo_total_empleador',

        // Resultado
        'salario_neto',
        'liquidada',
        'fecha_liquidacion',
        'liquidado_por',
        'estado_contable',
        'fecha_aprobacion_contable',
        'fecha_cierre_contable',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
        'horas_normales' => 'decimal:2',
        'horas_extras_nocturnas' => 'decimal:2',
        'horas_extras_diurnas' => 'decimal:2',
        'horas_festivas' => 'decimal:2',
        'horas_nocturnas_festivas' => 'decimal:2',
        'valor_hora_normal' => 'decimal:2',
        'valor_hora_nocturna' => 'decimal:2',
        'valor_hora_dominical' => 'decimal:2',
        'valor_hora_dominical_extra' => 'decimal:2',
        'salario_base_devengado' => 'decimal:2',
        'auxilio_transporte' => 'decimal:2',
        'total_comisiones' => 'decimal:2',
        'total_novedades_retroactivas' => 'decimal:2',
        'detalle_novedades_retroactivas' => 'array',
        'valor_horas_normales' => 'decimal:2',
        'valor_horas_extras_nocturnas' => 'decimal:2',
        'valor_horas_extras_diurnas' => 'decimal:2',
        'valor_horas_festivas' => 'decimal:2',
        'valor_horas_nocturnas_festivas' => 'decimal:2',
        'total_devengado' => 'decimal:2',
        'deduccion_salud' => 'decimal:2',
        'deduccion_pension' => 'decimal:2',
        'total_descuentos_adicionales' => 'decimal:2',
        'total_deducciones' => 'decimal:2',
        'base_aportes_empleador' => 'decimal:2',
        'porcentaje_salud_empleador' => 'decimal:3',
        'porcentaje_pension_empleador' => 'decimal:3',
        'porcentaje_arl' => 'decimal:3',
        'porcentaje_sena' => 'decimal:3',
        'porcentaje_icbf' => 'decimal:3',
        'porcentaje_caja_compensacion' => 'decimal:3',
        'costo_salud_empleador' => 'decimal:2',
        'costo_pension_empleador' => 'decimal:2',
        'costo_arl' => 'decimal:2',
        'costo_sena' => 'decimal:2',
        'costo_icbf' => 'decimal:2',
        'costo_caja_compensacion' => 'decimal:2',
        'costo_parafiscales' => 'decimal:2',
        'costo_total_empleador' => 'decimal:2',
        'salario_neto' => 'decimal:2',
        'liquidada' => 'boolean',
        'fecha_liquidacion' => 'datetime',
        'fecha_aprobacion_contable' => 'datetime',
        'fecha_cierre_contable' => 'datetime',
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

    public function descuento()
    {
        return $this->belongsTo(Descuento::class, 'descuento_id');
    }

    public function transacionalRegistro()
    {
        return $this->belongsTo(TransacionalRegistro::class, 'transacional_registros_id');
    }

    public function jornadaLaboral()
    {
        return $this->belongsTo(JornadaLaboral::class, 'jornada_laboral_id');
    }

    public function comisiones()
    {
        return $this->hasMany(Comision::class, 'nomina_id');
    }

    public function novedadesRetroactivas()
    {
        return $this->hasMany(NovedadRetroactiva::class, 'nomina_id');
    }

    public function liquidacionRetiro()
    {
        return $this->hasOne(LiquidacionRetiro::class, 'nomina_id');
    }

    public function preliquidacion()
    {
        return $this->belongsTo(PreliquidacionNomina::class, 'preliquidacion_id');
    }

    public function liquidador()
    {
        return $this->belongsTo(User::class, 'liquidado_por');
    }
}
