<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CapacitacionEncuesta extends Model
{
    use SoftDeletes;

    protected $table = 'capacitacion_encuestas';

    protected $fillable = [
        'uuid',
        'capacitacion_id',
        'user_id',
        'titulo',
        'descripcion',
        'estado',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid ??= Str::uuid()->toString();
        });
    }

    public function capacitacion()
    {
        return $this->belongsTo(Capacitacion::class, 'capacitacion_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function preguntas()
    {
        return $this->hasMany(CapacitacionEncuestaPregunta::class, 'encuesta_id')->orderBy('orden');
    }

    public function envios()
    {
        return $this->hasMany(CapacitacionEncuestaEnvio::class, 'encuesta_id');
    }
}
