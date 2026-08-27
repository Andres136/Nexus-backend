<?php

namespace App\Models\Hseq;

use App\Models\Departamentos;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditoriaPregunta extends Model
{
    // Clasificación del hallazgo para el informe de auditoría.
    public const TIPOS_HALLAZGO = ['no_conformidad', 'oportunidad_mejora', 'observacion', 'fortaleza'];

    protected $fillable = [
        'auditoria_id',
        'proceso_id',
        'tipo_hallazgo',
        'pregunta',
        'calificacion',
        'observaciones',
        'orden',
    ];

    public function auditoria()
    {
        return $this->belongsTo(Auditoria::class);
    }

    // "Proceso auditado" = el departamento (catálogo real y poblado del sistema).
    public function proceso()
    {
        return $this->belongsTo(Departamentos::class, 'proceso_id');
    }

    // Una pregunta puede homologar varias normas/cláusulas a la vez (ej. aplica tanto a
    // ISO 9001 como a ISO 14001).
    public function clausulas()
    {
        return $this->belongsToMany(ClausulaIso::class, 'auditoria_pregunta_clausulas')->withTimestamps();
    }

    // Personas del proceso/departamento asignado que fueron entrevistadas para esta pregunta
    // (para el informe: "PERSONAS AUDITADAS").
    public function personasAuditadas()
    {
        return $this->belongsToMany(User::class, 'auditoria_pregunta_personas')->withTimestamps();
    }
}
