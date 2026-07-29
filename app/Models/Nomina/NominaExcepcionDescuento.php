<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NominaExcepcionDescuento extends Model
{
    protected $table = 'nomina_excepciones_descuento';

    protected $fillable = [
        'uuid',
        'user_id',
        'periodo_inicio',
        'periodo_fin',
        'descontar_tardanzas',
        'descontar_permisos',
        'actualizado_por',
    ];

    protected $casts = [
        'periodo_inicio'      => 'date',
        'periodo_fin'         => 'date',
        'descontar_tardanzas' => 'boolean',
        'descontar_permisos'  => 'boolean',
        'uuid'                => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
