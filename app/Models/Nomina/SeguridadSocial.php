<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SeguridadSocial extends Model
{
    use SoftDeletes;

    protected $table = 'seguridad_socials';

    protected $fillable = [
        'uuid',
        'nombre',
        'nit',
        'direccion',
        'fecha_inicio',
        'fecha_fin',
        'status',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}