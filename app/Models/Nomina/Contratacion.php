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
        'tipo_salario',
        'salario_integral',
        'parametro_laboral_id',
        'no_salarial',
        'base_salario',
        'auxilio_transporte',
        'pago_frecuencia',
        'inicio_contratacion',
        'dias_vacaciones_iniciales',
        'fin_contrato',
        'status',
        'eps_id',
        'arl_id',
        'fondo_pensiones_id',
        'caja_penciones_id',
        'fondo_cesantias_id',
        'aplica_salud',
        'aplica_pension',
        'aplica_arl',
        'aplica_sena',
        'aplica_icbf',
        'aplica_caja_compensacion',
    ];

    protected $casts = [
        'no_salarial'         => 'decimal:2',
        'base_salario'        => 'decimal:2',
        'auxilio_transporte'  => 'decimal:2',
        'dias_vacaciones_iniciales' => 'decimal:4',
        'inicio_contratacion' => 'date',
        'fin_contrato'        => 'datetime',
        'status'              => 'boolean',
        'salario_integral'    => 'boolean',
        'aplica_salud'        => 'boolean',
        'aplica_pension'      => 'boolean',
        'aplica_arl'          => 'boolean',
        'aplica_sena'         => 'boolean',
        'aplica_icbf'         => 'boolean',
        'aplica_caja_compensacion' => 'boolean',
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

    public function fondoCesantias()
    {
        return $this->belongsTo(SeguridadSocial::class, 'fondo_cesantias_id')->withTrashed();
    }

    public function liquidacionRetiro()
    {
        return $this->hasOne(LiquidacionRetiro::class, 'contratacion_id');
    }

    public function historialSalarial()
    {
        return $this->hasMany(HistorialSalarialContratacion::class, 'contratacion_id');
    }

    public function cambiosContractuales()
    {
        return $this->hasMany(ContratacionCambio::class, 'contratacion_id');
    }

    public function nominas()
    {
        return $this->hasMany(Nomina::class, 'contratacion_id');
    }

    public function parametroLaboral()
    {
        return $this->belongsTo(NominaParametroLaboral::class, 'parametro_laboral_id');
    }
}
