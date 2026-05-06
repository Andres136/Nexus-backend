<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Valor extends Model
{
    use SoftDeletes;

    protected $table = 'valores';

    protected $fillable = [
        'uuid',
        'valor_hora_normal',
        'valor_hora_nocturna',
        'valor_hora_dominical',
        'valor_hora_dominical_extra',
        'status',
    ];

    protected $casts = [
        'valor_hora_normal'         => 'decimal:2',
        'valor_hora_nocturna'       => 'decimal:2',
        'valor_hora_dominical'      => 'decimal:2',
        'valor_hora_dominical_extra'=> 'decimal:2',
        'status'                    => 'boolean',
        'uuid'                      => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}