<?php

namespace App\Models\Hseq;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Auditoria extends Model
{
    protected $fillable = [
        'uuid',
        'fecha_inicio',
        'fecha_fin',
        'hora',
        'lugar',
        'objetivo',
        'alcance',
        'estado',
        'observaciones',
        'calificacion_final',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $auditoria) {
            $auditoria->uuid ??= (string) Str::uuid();
        });
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // Equipo auditor: quien crea la auditoría queda como primer participante y puede sumar
    // más; solo estos usuarios (o ADMINISTRADOR/HSEQ) pueden ver/gestionar la auditoría.
    public function participantes()
    {
        return $this->belongsToMany(User::class, 'auditoria_participantes')->withTimestamps();
    }

    public function preguntas()
    {
        return $this->hasMany(AuditoriaPregunta::class)->orderBy('orden');
    }
}
