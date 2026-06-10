<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HistorialSalarialContratacion extends Model
{
    use SoftDeletes;

    protected $table = 'historial_salarial_contrataciones';

    protected $fillable = [
        'uuid',
        'contratacion_id',
        'user_id',
        'tipo_ajuste',
        'salario_anterior',
        'salario_nuevo',
        'auxilio_anterior',
        'auxilio_nuevo',
        'no_salarial_anterior',
        'no_salarial_nuevo',
        'porcentaje_aumento',
        'fecha_vigencia',
        'motivo',
        'observacion',
        'aprobado_por',
        'status',
    ];

    protected $casts = [
        'salario_anterior' => 'decimal:2',
        'salario_nuevo' => 'decimal:2',
        'auxilio_anterior' => 'decimal:2',
        'auxilio_nuevo' => 'decimal:2',
        'no_salarial_anterior' => 'decimal:2',
        'no_salarial_nuevo' => 'decimal:2',
        'porcentaje_aumento' => 'decimal:4',
        'fecha_vigencia' => 'date',
        'status' => 'boolean',
        'uuid' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function contratacion()
    {
        return $this->belongsTo(Contratacion::class, 'contratacion_id');
    }

    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
}
