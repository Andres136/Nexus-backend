<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ContratacionCambio extends Model
{
    use SoftDeletes;

    protected $table = 'contratacion_cambios';

    protected $fillable = [
        'uuid',
        'contratacion_id',
        'user_id',
        'tipo_cambio',
        'fecha_cambio',
        'motivo',
        'observaciones',
        'datos_anteriores',
        'datos_nuevos',
        'registrado_por',
    ];

    protected $casts = [
        'fecha_cambio' => 'date',
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function contratacion()
    {
        return $this->belongsTo(Contratacion::class, 'contratacion_id');
    }

    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
