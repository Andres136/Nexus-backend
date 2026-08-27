<?php

namespace App\Services\Hseq;

use App\Models\Hseq\Auditoria;
use App\Models\Hseq\AuditoriaPregunta;
use App\Models\User;
use App\RolEnum;

class AuditoriaService
{
    private const ROLES_GESTION = [RolEnum::ADMINISTRADOR, RolEnum::HSEQ];

    // ADMINISTRADOR/HSEQ gestionan todas las auditorías; el resto solo las suyas (donde son
    // participantes). Se centraliza aquí para que el controller de auditorías y el de sus
    // preguntas apliquen exactamente la misma regla.
    public function puedeGestionarTodas(User $user): bool
    {
        return in_array(RolEnum::tryFrom((int) $user->role_id), self::ROLES_GESTION, true);
    }

    public function puedeAcceder(User $user, Auditoria $auditoria): bool
    {
        return $this->puedeGestionarTodas($user) || $this->esParticipante($auditoria, $user->id);
    }

    //listar auditorias

    public function all(array $filtros = [])
    {
        $query = Auditoria::with([
            'participantes:id,name',
            'creador:id,name',
        ])->withCount('preguntas');

        if (!empty($filtros['participante_id'])) {
            $query->whereHas('participantes', function ($q) use ($filtros) {
                $q->where('user_id', $filtros['participante_id']);
            });
        }

        // Filtra por el mes/rango visible del calendario: una auditoría aparece si su rango de
        // fechas se solapa con [desde, hasta] (mismo patrón que DeliveryEventController::index).
        if (!empty($filtros['desde'])) {
            $query->where('fecha_fin', '>=', $filtros['desde']);
        }

        if (!empty($filtros['hasta'])) {
            $query->where('fecha_inicio', '<=', $filtros['hasta']);
        }

        return $query->orderBy('fecha_inicio', 'desc')->get();
    }

    public function find($id)
    {
        return Auditoria::with([
            'participantes:id,name',
            'creador:id,name',
            'preguntas.proceso:id,nombre',
            'preguntas.clausulas.norma',
            'preguntas.personasAuditadas:id,name',
        ])->findOrFail($id);
    }

    // ¿Puede este usuario ver/gestionar la auditoría? Solo su equipo de participantes (o quien
    // tenga rol de gestión, chequeado aparte en el controller) — se usa en show/update/destroy/
    // finalizar y en las acciones sobre preguntas, para que nadie ajeno a la auditoría acceda
    // por más que adivine el id.
    public function esParticipante(Auditoria $auditoria, int $userId): bool
    {
        return $auditoria->participantes()->where('user_id', $userId)->exists();
    }

    public function create(array $data)
    {
        $auditoria = Auditoria::create([
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'hora' => $data['hora'] ?? null,
            'lugar' => $data['lugar'] ?? null,
            'objetivo' => $data['objetivo'] ?? null,
            'alcance' => $data['alcance'] ?? null,
            'estado' => 'programada',
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        // No es mass-assignable: se fija aparte para que nadie pueda mandarlo por el request.
        $auditoria->creado_por = auth()->id();
        $auditoria->save();

        // Quien crea la auditoría queda como primer participante; se pueden sumar más desde el
        // mismo formulario (o después, con agregarParticipante()).
        $participantes = array_unique(array_merge([auth()->id()], $data['participantes'] ?? []));
        $auditoria->participantes()->sync($participantes);

        return $auditoria->load(['participantes:id,name', 'creador:id,name']);
    }

    public function update($id, array $data)
    {
        $auditoria = $this->find($id);

        if ($auditoria->estado === 'completada') {
            throw new \RuntimeException('No se puede editar una auditoría que ya fue completada.');
        }

        if ($auditoria->estado === 'programada' && $auditoria->fecha_fin < now()->toDateString()) {
            throw new \RuntimeException('No se puede editar una auditoría cuyo rango de fechas ya pasó.');
        }

        // 'estado' y 'calificacion_final' quedan fuera aunque el modelo los tenga fillable
        // (los necesita el propio Service para agregarPregunta()/finalizar()): esta ruta
        // genérica de edición nunca debe permitir que el cliente los mande directamente.
        $auditoria->update(collect($data)->only([
            'fecha_inicio', 'fecha_fin', 'hora', 'lugar', 'objetivo', 'alcance', 'observaciones',
        ])->toArray());

        return $auditoria;
    }

    public function agregarParticipante(Auditoria $auditoria, int $userId)
    {
        if ($auditoria->estado === 'completada') {
            throw new \RuntimeException('No se pueden agregar participantes a una auditoría completada.');
        }

        $auditoria->participantes()->syncWithoutDetaching([$userId]);
        return $auditoria->load('participantes:id,name');
    }

    public function quitarParticipante(Auditoria $auditoria, int $userId)
    {
        if ($auditoria->participantes()->count() <= 1) {
            throw new \RuntimeException('La auditoría debe tener al menos un participante.');
        }

        $auditoria->participantes()->detach($userId);
        return $auditoria->load('participantes:id,name');
    }

    public function delete($id)
    {
        $auditoria = $this->find($id);

        if ($auditoria->estado === 'completada') {
            throw new \RuntimeException('No se puede eliminar una auditoría que ya fue completada.');
        }

        $auditoria->delete();
        return $auditoria;
    }

    public function agregarPregunta(Auditoria $auditoria, array $data)
    {
        if ($auditoria->estado === 'completada') {
            throw new \RuntimeException('No se pueden agregar preguntas a una auditoría completada.');
        }

        $orden = (int) $auditoria->preguntas()->max('orden') + 1;

        // 'proceso_id' es opcional aquí a propósito: al planificar (formulario de creación) solo
        // se deciden las cláusulas ISO + el texto; el proceso se asigna después, al ejecutar.
        $pregunta = $auditoria->preguntas()->create([
            'proceso_id' => $data['proceso_id'] ?? null,
            'pregunta' => $data['pregunta'],
            'orden' => $orden,
        ]);

        // Una pregunta puede homologar varias normas/cláusulas a la vez.
        $pregunta->clausulas()->sync($data['clausulas_iso']);

        return $pregunta->load(['proceso:id,nombre', 'clausulas.norma', 'personasAuditadas:id,name']);
    }

    public function actualizarPregunta($id, array $data)
    {
        $pregunta = AuditoriaPregunta::with('auditoria')->findOrFail($id);
        $this->validarPreguntaEditable($pregunta);

        $pregunta->update(collect($data)->except(['clausulas_iso', 'personas_auditadas'])->toArray());

        if (isset($data['clausulas_iso'])) {
            $pregunta->clausulas()->sync($data['clausulas_iso']);
        }

        // Personas del proceso asignado que fueron entrevistadas, para el informe.
        if (isset($data['personas_auditadas'])) {
            $pregunta->personasAuditadas()->sync($data['personas_auditadas']);
        }

        return $pregunta->load(['proceso:id,nombre', 'clausulas.norma', 'personasAuditadas:id,name']);
    }

    public function eliminarPregunta($id)
    {
        $pregunta = AuditoriaPregunta::with('auditoria')->findOrFail($id);
        $this->validarPreguntaEditable($pregunta);

        $pregunta->delete();
        return $pregunta;
    }

    private function validarPreguntaEditable(AuditoriaPregunta $pregunta)
    {
        if ($pregunta->auditoria->estado === 'completada') {
            throw new \RuntimeException('No se puede modificar una pregunta de una auditoría completada.');
        }

        if (!is_null($pregunta->calificacion)) {
            throw new \RuntimeException('No se puede modificar una pregunta que ya fue calificada.');
        }
    }

    public function calificarPregunta($id, array $data)
    {
        $pregunta = AuditoriaPregunta::with('auditoria')->findOrFail($id);

        if ($pregunta->auditoria->estado === 'completada') {
            throw new \RuntimeException('No se puede calificar una pregunta de una auditoría completada.');
        }

        if (is_null($pregunta->proceso_id)) {
            throw new \RuntimeException('Asigna el proceso auditado antes de calificar esta pregunta.');
        }

        $pregunta->update([
            'calificacion' => $data['calificacion'],
            'observaciones' => $data['observaciones'] ?? $pregunta->observaciones,
            'tipo_hallazgo' => $data['tipo_hallazgo'] ?? $pregunta->tipo_hallazgo,
        ]);

        // La calificación es la acción real de "ejecutar": recién aquí pasa de programada a
        // en_ejecucion (agregar/planificar preguntas de antemano no cuenta como haber empezado).
        if ($pregunta->auditoria->estado === 'programada') {
            $pregunta->auditoria->update(['estado' => 'en_ejecucion']);
        }

        return $pregunta->load(['proceso:id,nombre', 'clausulas.norma', 'personasAuditadas:id,name']);
    }

    public function finalizar($id)
    {
        $auditoria = $this->find($id);
        $calificadas = $auditoria->preguntas->whereNotNull('calificacion');

        if ($calificadas->isEmpty()) {
            throw new \RuntimeException('No se puede finalizar una auditoría sin preguntas calificadas.');
        }

        $auditoria->update([
            'calificacion_final' => round($calificadas->avg('calificacion'), 2),
            'estado' => 'completada',
        ]);

        return $auditoria->load([
            'preguntas.proceso:id,nombre',
            'preguntas.clausulas.norma',
            'preguntas.personasAuditadas:id,name',
        ]);
    }
}
