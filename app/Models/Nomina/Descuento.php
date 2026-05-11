<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Descuento extends Model
{
    use SoftDeletes;

    protected $table = 'descuentos';

    protected $fillable = [
        'uuid',
        'user_id',
        'monto',
        'inicio',
        'fin',
        'status',
        'concepto_descuento',
        'numero_cuotas',
        'valor_cuota',
        'frecuencia_pago',
    ];

    protected $casts = [
        'inicio'  => 'date',
        'fin'     => 'date',
        'status'  => 'boolean',
        'monto'   => 'decimal:2',
        'valor_cuota' => 'decimal:2',
        'uuid'    => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    // ¿A qué empleado pertenece este descuento?
    public function empleado()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }


  
}