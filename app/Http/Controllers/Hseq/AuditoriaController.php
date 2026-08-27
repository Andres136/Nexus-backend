<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreAuditoriaRequest;
use App\Models\User;
use App\Services\Hseq\AuditoriaService;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    protected AuditoriaService $auditoriaService;

    public function __construct(AuditoriaService $auditoriaService)
    {
        $this->auditoriaService = $auditoriaService;
    }

    private function puedeGestionarTodas(User $user): bool
    {
        return $this->auditoriaService->puedeGestionarTodas($user);
    }

    // Solo el equipo de participantes de la auditoría (o quien tenga rol de gestión) puede
    // verla/actuar sobre ella — evita que cualquier usuario autenticado acceda adivinando el id.
    private function puedeVer(User $user, string $id): bool
    {
        return $this->auditoriaService->puedeAcceder($user, $this->auditoriaService->find($id));
    }

    private function denegado()
    {
        return response()->json([
            'message' => 'No tienes acceso a esta auditoría.',
        ], 403);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $usuario = $request->user();
        $filtros = $request->query();

        if (!$this->puedeGestionarTodas($usuario)) {
            $filtros['participante_id'] = $usuario->id;
        }

        $auditorias = $this->auditoriaService->all($filtros);

        return response()->json([
            'data' => $auditorias
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuditoriaRequest $request)
    {
        $data = $request->validated();
        $auditoria = $this->auditoriaService->create($data);
        return response()->json([
            'message' => 'Auditoría programada exitosamente',
            'data' => $auditoria
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        if (!$this->puedeVer($request->user(), $id)) {
            return $this->denegado();
        }

        $auditoria = $this->auditoriaService->find($id);
        return response()->json([
            'data' => $auditoria
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!$this->puedeVer($request->user(), $id)) {
            return $this->denegado();
        }

        $data = $request->all();
        try {
            $auditoria = $this->auditoriaService->update($id, $data);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json([
            'message' => 'Auditoría actualizada exitosamente',
            'data' => $auditoria
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        if (!$this->puedeVer($request->user(), $id)) {
            return $this->denegado();
        }

        try {
            $auditoria = $this->auditoriaService->delete($id);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json([
            'message' => 'Auditoría eliminada exitosamente',
            'data' => $auditoria
        ]);
    }

    /**
     * Finalize the audit, computing the final rating.
     */
    public function finalizar(Request $request, string $id)
    {
        if (!$this->puedeVer($request->user(), $id)) {
            return $this->denegado();
        }

        try {
            $auditoria = $this->auditoriaService->finalizar($id);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json([
            'message' => 'Auditoría finalizada exitosamente',
            'data' => $auditoria
        ]);
    }

    /**
     * Add a participant (auditor) to the audit team.
     */
    public function agregarParticipante(Request $request, string $id)
    {
        if (!$this->puedeVer($request->user(), $id)) {
            return $this->denegado();
        }

        $data = $request->validate(['user_id' => 'required|exists:users,id']);
        $auditoria = $this->auditoriaService->find($id);

        try {
            $auditoria = $this->auditoriaService->agregarParticipante($auditoria, (int) $data['user_id']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Participante agregado exitosamente',
            'data' => $auditoria
        ]);
    }

    /**
     * Remove a participant (auditor) from the audit team.
     */
    public function quitarParticipante(Request $request, string $id, string $userId)
    {
        if (!$this->puedeVer($request->user(), $id)) {
            return $this->denegado();
        }

        $auditoria = $this->auditoriaService->find($id);

        try {
            $auditoria = $this->auditoriaService->quitarParticipante($auditoria, (int) $userId);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Participante eliminado exitosamente',
            'data' => $auditoria
        ]);
    }
}
