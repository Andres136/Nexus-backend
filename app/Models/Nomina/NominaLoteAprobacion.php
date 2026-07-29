<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NominaLoteAprobacion extends Model
{
    protected $table = 'nomina_lotes_aprobacion';

    protected $fillable = [
        'uuid',
        'periodo_inicio',
        'periodo_fin',
        'preliquidacion_ids',
        'responsable_id',
        'generado_por',
        'estado',
        'aprobado_en',
        'resultado',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
        'preliquidacion_ids' => 'array',
        'resultado' => 'array',
        'aprobado_en' => 'datetime',
        'uuid' => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function generadoPor()
    {
        return $this->belongsTo(User::class, 'generado_por');
    }
}
