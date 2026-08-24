<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreEficaciaEvaluacionRequest;
use App\Services\Hseq\EficaciaEvaluacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EficaciaEvaluacionController extends Controller
{
    protected $eficaciaEvaluacionService;

    public function __construct(EficaciaEvaluacionService $eficaciaEvaluacionService)
    {
        $this->eficaciaEvaluacionService = $eficaciaEvaluacionService;
    }

    public function index(string $novedadId): JsonResponse
    {
        return response()->json([
            'data' => $this->eficaciaEvaluacionService->listarPorNovedad($novedadId),
        ]);
    }

    public function store(StoreEficaciaEvaluacionRequest $request, string $novedadId): JsonResponse
    {
        try {
            $evaluacion = $this->eficaciaEvaluacionService->registrar(
                $novedadId,
                $request->validated(),
                auth()->id()
            );

            return response()->json([
                'message' => 'Evaluación de eficacia registrada correctamente',
                'data' => $evaluacion,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    public function actualizarProximaVerificacion(Request $request, string $novedadId, string $eficaciaId): JsonResponse
    {
        $data = $request->validate([
            'proxima_verificacion' => 'nullable|date',
        ]);

        $evaluacion = $this->eficaciaEvaluacionService->actualizarProximaVerificacion(
            $novedadId,
            $eficaciaId,
            $data['proxima_verificacion'] ?? null
        );

        return response()->json([
            'message' => 'Próxima verificación actualizada correctamente',
            'data' => $evaluacion,
        ]);
    }
}
