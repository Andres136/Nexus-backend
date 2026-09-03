<?php

namespace App\Models\Hseq;

use App\Models\Departamentos;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Auditoria extends Model
{
    protected $fillable = [
        'uuid',
        'departamento_id',
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

    // Departamento (proceso) al que se le hace la auditoría, elegido al programarla.
    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    }

    // Equipo auditor: quien crea la auditoría queda como primer participante y puede sumar
    // más; solo estos usuarios (o ADMINISTRADOR/HSEQ) pueden ver/gestionar la auditoría.
    public function participantes()
    {
        return $this->belongsToMany(User::class, 'auditoria_participantes')->withTimestamps();
    }

    // Orden de ejecución: agrupadas por proceso (las ya asignadas primero) y, dentro de cada
    // proceso, por la hora de agenda; el 'orden' de planificación queda como desempate.
    public function preguntas()
    {
        return $this->hasMany(AuditoriaPregunta::class)
            ->orderByRaw('proceso_id IS NULL')
            ->orderBy('proceso_id')
            ->orderByRaw('hora IS NULL')
            ->orderBy('hora')
            ->orderBy('orden');
    }
}
