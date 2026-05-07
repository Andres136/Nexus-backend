<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TipoContrato extends Model
{
    use SoftDeletes;

    protected $table = 'tipo_contratos';

    protected $fillable = [
        'uuid',
        'nombre',
        'codigo',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function contrataciones()
    {
        return $this->hasMany(Contratacion::class, 'id_contrato');
    }
}
