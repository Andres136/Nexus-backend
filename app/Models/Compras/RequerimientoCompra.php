<?php

namespace App\Models\Compras;

use App\Models\Crm\bodega;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RequerimientoCompra extends Model
{
    use SoftDeletes;

    public const ESTADO_SOLICITADO = 'solicitado';
    public const ESTADO_EN_ANALISIS = 'en_analisis';
    public const ESTADO_APROBADO = 'aprobado';
    public const ESTADO_RECHAZADO = 'rechazado';
    public const ESTADO_OC_GENERADA = 'oc_generada';
    public const ESTADO_CANCELADO = 'cancelado';

    protected $table = 'requerimientos_compra';

    protected $fillable = [
        'uuid',
        'codigo',
        'user_id',
        'sede_id',
        'bodega_id',
        'orden_trabajo_id',
        'prioridad',
        'estado',
        'fecha_requerida',
        'fecha_solicitud',
        'observacion',
        'rechazado_por',
        'rechazado_at',
        'motivo_rechazo',
        'analizado_por',
        'analizado_at',
        'orden_compra_id',
        'generado_oc_por',
        'generado_oc_at',
    ];

    protected $casts = [
        'fecha_requerida' => 'date',
        'fecha_solicitud' => 'datetime',
        'rechazado_at' => 'datetime',
        'analizado_at' => 'datetime',
        'generado_oc_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function bodega()
    {
        return $this->belongsTo(bodega::class, 'bodega_id');
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompraProveedor::class, 'orden_compra_id');
    }

    public function detalles()
    {
        return $this->hasMany(RequerimientoCompraDetalle::class, 'requerimiento_compra_id');
    }

    public function eventos()
    {
        return $this->hasMany(RequerimientoCompraEvento::class, 'requerimiento_compra_id');
    }
}
