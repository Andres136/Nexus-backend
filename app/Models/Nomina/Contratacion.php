<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Contratacion extends Model
{
    use SoftDeletes;

    protected $table = 'contrataciones';

    protected $fillable = [
        'uuid',
        'id_contrato',
        'users_id',
        'no_salarial',
        'base_salario',
        'auxilio_transporte',
        'pago_frecuencia',
        'inicio_contratacion',
        'fin_contrato',
        'status',
        'eps_id',
        'arl_id',
        'fondo_pensiones_id',
        'caja_pensiones_id',
    ];

    protected $casts = [
        'no_salarial'        => 'decimal:2',
        'base_salario'       => 'decimal:2',
        'auxilio_transporte' => 'decimal:2',
        'inicio_contratacion'=> 'date',
        'fin_contrato'       => 'datetime',
        'status'             => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    // Relaciones
    public function tipoContrato()
    {
        return $this->belongsTo(TipoContrato::class, 'id_contrato');
    }

    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class, 'users_id');
    }
}
