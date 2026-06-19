<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PreliquidacionNominaAjuste extends Model
{
    use SoftDeletes;

    protected $table = 'preliquidacion_nomina_ajustes';

    protected $guarded = [];

    protected $casts = [
        'valor' => 'decimal:2',
        'afecta_base_aportes' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid ??= Str::uuid();
        });
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
