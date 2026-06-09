<?php

namespace App\Models\Nomina;

use App\Models\Crm\empresa;
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
        'empresa_id',
        'tipo_documento',
        'numero_documento',
        'correo',
        'cargo',
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
        'caja_penciones_id',
    ];

    protected $casts = [
        'no_salarial'         => 'decimal:2',
        'base_salario'        => 'decimal:2',
        'auxilio_transporte'  => 'decimal:2',
        'inicio_contratacion' => 'date',
        'fin_contrato'        => 'datetime',
        'status'              => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function empresa()
    {
        return $this->belongsTo(empresa::class, 'empresa_id');
    }

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
        return $this->belongsTo(SeguridadSocial::class, 'eps_id')->withTrashed();
    }

    public function arl()
    {
        return $this->belongsTo(SeguridadSocial::class, 'arl_id')->withTrashed();
    }

    public function fondoPensiones()
    {
        return $this->belongsTo(SeguridadSocial::class, 'fondo_pensiones_id')->withTrashed();
    }

    public function cajaPenciones()
    {
        return $this->belongsTo(SeguridadSocial::class, 'caja_penciones_id')->withTrashed();
    }

    public function cajaCompensacion()
    {
        return $this->belongsTo(SeguridadSocial::class, 'caja_penciones_id')->withTrashed();
    }

    public function liquidacionRetiro()
    {
        return $this->hasOne(LiquidacionRetiro::class, 'contratacion_id');
    }
}
