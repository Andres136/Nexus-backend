<?php

namespace App\Models\Productividad;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CorreccionActividadOperativa extends Model
{
    use SoftDeletes;

    protected $table = 'correcciones_actividad_operativa';

    protected $fillable = [
        'uuid',
        'actividad_operativa_id',
        'user_id',
        'motivo',
        'observaciones',
        'datos_anteriores',
        'datos_nuevos',
        'registrado_por',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });
    }

    public function actividadOperativa()
    {
        return $this->belongsTo(ActividadOperativa::class, 'actividad_operativa_id');
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
