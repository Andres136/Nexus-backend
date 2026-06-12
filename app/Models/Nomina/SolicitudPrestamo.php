<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SolicitudPrestamo extends Model
{
    use SoftDeletes;

    protected $table = 'solicitudes_prestamos';

    protected $fillable = [
        'uuid',
        'user_id',
        'descuento_id',
        'gestionado_por_id',
        'monto_solicitado',
        'numero_cuotas_solicitadas',
        'frecuencia_pago_solicitada',
        'motivo',
        'status',
        'monto_aprobado',
        'tasa_interes_porcentaje',
        'valor_interes',
        'total_a_descontar',
        'numero_cuotas_aprobadas',
        'valor_cuota_aprobada',
        'frecuencia_pago_aprobada',
        'inicio_descuento',
        'observacion_nomina',
        'fecha_gestion',
    ];

    protected $casts = [
        'monto_solicitado' => 'decimal:2',
        'monto_aprobado' => 'decimal:2',
        'tasa_interes_porcentaje' => 'decimal:2',
        'valor_interes' => 'decimal:2',
        'total_a_descontar' => 'decimal:2',
        'valor_cuota_aprobada' => 'decimal:2',
        'inicio_descuento' => 'date',
        'fecha_gestion' => 'datetime',
        'uuid' => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = $model->uuid ?: Str::uuid();
        });
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function gestionadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestionado_por_id');
    }

    public function descuento(): BelongsTo
    {
        return $this->belongsTo(Descuento::class, 'descuento_id');
    }
}
