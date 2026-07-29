<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PreliquidacionNomina extends Model
{
    use SoftDeletes;

    protected $table = 'preliquidaciones_nomina';

    protected $guarded = [];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
        'calculo_original' => 'array',
        'calculo_ajustado' => 'array',
        'total_devengado_original' => 'decimal:2',
        'total_deducciones_original' => 'decimal:2',
        'salario_neto_original' => 'decimal:2',
        'total_devengado_ajustado' => 'decimal:2',
        'total_deducciones_ajustado' => 'decimal:2',
        'salario_neto_ajustado' => 'decimal:2',
        'fecha_revision' => 'datetime',
        'fecha_aprobacion' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid ??= Str::uuid();
        });
    }

    public function ajustes()
    {
        return $this->hasMany(PreliquidacionNominaAjuste::class, 'preliquidacion_id');
    }

    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function generadoPor()
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function nomina()
    {
        return $this->hasOne(Nomina::class, 'preliquidacion_id');
    }
}
