<?php

namespace App\Models\Nomina;

use App\Models\contabilidad\Puck;
use App\Support\Nomina\NominaConceptoContableCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class NominaConceptoContable extends Model
{
    use SoftDeletes;

    protected $table = 'nomina_conceptos_contables';

    protected $fillable = [
        'uuid',
        'codigo',
        'nombre',
        'tipo',
        'puck_id',
        'naturaleza',
        'requiere_tercero',
        'requiere_centro_costo',
        'activo',
    ];

    protected $casts = [
        'requiere_tercero' => 'boolean',
        'requiere_centro_costo' => 'boolean',
        'activo' => 'boolean',
        'uuid' => 'string',
    ];

    protected $appends = [
        'puck_numero_sugerido',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid ??= Str::uuid();
        });
    }

    public function puck()
    {
        return $this->belongsTo(Puck::class, 'puck_id');
    }

    public function getPuckNumeroSugeridoAttribute(): ?string
    {
        return NominaConceptoContableCatalog::puckNumero($this->codigo);
    }
}
