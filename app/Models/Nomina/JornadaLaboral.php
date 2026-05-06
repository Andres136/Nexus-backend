<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class JornadaLaboral extends Model
{
    use SoftDeletes;

    protected $table = 'jornada_laborals';

    protected $fillable = [
        'uuid',
        'nombre',
        'horas_semanales',
        'status',
    ];

    protected $casts = [
        'status'          => 'boolean',
        'horas_semanales' => 'integer',
        'uuid'            => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}