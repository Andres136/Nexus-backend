<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Cuestionario extends Model
{
    protected $table = 'convocatoria_cuestionarios';

    protected $fillable = [
        'uuid',
        'convocatoria_id',
        'titulo',
        'descripcion',
        'duracion_segundos',
        'estado',
        'publicado_en',
        'cerrado_en',
        'creado_por',
    ];

    protected $casts = [
        'publicado_en' => 'datetime',
        'cerrado_en' => 'datetime',
        'duracion_segundos' => 'integer',
    ];

    protected $appends = ['deadline'];

    protected static function booted(): void
    {
        static::creating(function (self $cuestionario) {
            $cuestionario->uuid ??= (string) Str::uuid();
        });
    }

    public function convocatoria()
    {
        return $this->belongsTo(Convocatoria::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function preguntas()
    {
        return $this->hasMany(CuestionarioPregunta::class, 'cuestionario_id')->orderBy('orden');
    }

    public function respuestas()
    {
        return $this->hasMany(CuestionarioRespuesta::class, 'cuestionario_id');
    }

    public function getDeadlineAttribute(): ?string
    {
        if (! $this->publicado_en) {
            return null;
        }

        return $this->publicado_en->copy()->addSeconds($this->duracion_segundos)->toIso8601String();
    }
}
