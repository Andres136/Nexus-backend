<?php

namespace App\Models\Nomina;

use App\Models\User;
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
        return $this->belongsTo(User::class, 'users_id');
    }

    public function eps()
    {
        return $this->belongsTo(SeguridadSocial::class, 'eps_id');
    }
      // ARL
    public function arl()
    {
        return $this->belongsTo(
            \App\Models\Nomina\SeguridadSocial::class,
            'arl_id'
        )->withTrashed();
    }

    // 🔥 Fondo pensión
    public function fondoPensiones()
    {
        return $this->belongsTo(
            \App\Models\Nomina\SeguridadSocial::class,
            'fondo_pensiones_id'
        )->withTrashed();
    }

    // 🔥 Caja pensión
    public function cajaPensiones()
    {
        return $this->belongsTo(
            \App\Models\Nomina\SeguridadSocial::class,
            'caja_pensiones_id'
        )->withTrashed();
    }
}
