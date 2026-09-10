<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\CalificarAuditoriaPreguntaRequest;
use App\Http\Requests\Hseq\StoreAuditoriaPreguntaRequest;
use App\Http\Requests\Hseq\SugerirPreguntasAuditoriaRequest;
use App\Http\Requests\Hseq\UpdateAuditoriaPreguntaRequest;
use App\Models\Hseq\Auditoria;
use App\Models\Hseq\AuditoriaPregunta;
use App\Services\Hseq\AuditoriaService;
use App\Services\Hseq\GeneradorPreguntasAuditoriaService;
use Illuminate\Http\Request;

class AuditoriaPreguntaController extends Controller
{
    protected AuditoriaService $auditoriaService;
    protected GeneradorPreguntasAuditoriaService $generadorPreguntas;

    public function __construct(
        AuditoriaService $auditoriaService,
        GeneradorPreguntasAuditoriaService $generadorPreguntas
    ) {
        $this->auditoriaService = $auditoriaService;
        $this->generadorPreguntas = $generadorPreguntas;
    }

    private function denegado()
    {
        return response()->json([
            'message' => 'No tienes acceso a esta auditoría.',
        ], 403);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuditoriaPreguntaRequest $request)
    {
        $data = $request->validated();
        $auditoria = Auditoria::findOrFail($data['auditoria_id']);

        if (!$this->auditoriaService->puedeAcceder($request->user(), $auditoria)) {
            return $this->denegado();
        }

        try {
            $pregunta = $this->auditoriaService->agregarPregunta($auditoria, $data);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pregunta agregada exitosamente',
            'data' => $pregunta
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAuditoriaPreguntaRequest $request, string $id)
    {
        $pregunta = AuditoriaPregunta::with('auditoria')->findOrFail($id);
        if (!$this->auditoriaService->puedeAcceder($request->user(), $pregunta->auditoria)) {
            return $this->denegado();
        }

        $data = $request->validated();

        try {
            $pregunta = $this->auditoriaService->actualizarPregunta($id, $data);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pregunta actualizada exitosamente',
            'data' => $pregunta
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $pregunta = AuditoriaPregunta::with('auditoria')->findOrFail($id);
        if (!$this->auditoriaService->puedeAcceder($request->user(), $pregunta->auditoria)) {
            return $this->denegado();
        }

        try {
            $pregunta = $this->auditoriaService->eliminarPregunta($id);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pregunta eliminada exitosamente',
            'data' => $pregunta
        ]);
    }

    /**
     * Genera preguntas propuestas por IA a partir de los requisitos (cláusulas ISO)
     * seleccionados y, opcionalmente, del proceso auditado (departamento). No persiste
     * nada: el auditor revisa cada sugerencia y la guarda con store().
     *
     * Solo ADMINISTRADOR (1) y HSEQ (2): se restringe con el middleware 'role:1,2' en la ruta.
     */
    public function sugerir(SugerirPreguntasAuditoriaRequest $request, Auditoria $auditoria)
    {
        if (! $this->auditoriaService->puedeAcceder($request->user(), $auditoria)) {
            return $this->denegado();
        }

        if ($auditoria->estado === 'completada') {
            return response()->json([
                'message' => 'No se pueden sugerir preguntas para una auditoría completada.',
            ], 422);
        }

        $data = $request->validated();

        try {
            $preguntas = $this->generadorPreguntas->sugerir(
                $data['clausulas_iso'],
                $data['departamento_id'] ?? null,
                $data['cantidad'] ?? 5,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo obtener sugerencias de la IA en este momento.',
            ], 502);
        }

        return response()->json([
            'message' => 'Preguntas sugeridas generadas',
            'data' => $preguntas,
        ]);
    }

    /**
     * Rate the specified question with stars (1-5).
     */
    public function calificar(CalificarAuditoriaPreguntaRequest $request, string $id)
    {
        $pregunta = AuditoriaPregunta::with('auditoria')->findOrFail($id);
        if (!$this->auditoriaService->puedeAcceder($request->user(), $pregunta->auditoria)) {
            return $this->denegado();
        }

        $data = $request->validated();

        try {
            $pregunta = $this->auditoriaService->calificarPregunta($id, $data);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pregunta calificada exitosamente',
            'data' => $pregunta
        ]);
    }
}
