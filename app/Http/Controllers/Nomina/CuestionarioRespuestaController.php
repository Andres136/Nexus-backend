<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\EnviarRespuestasCuestionarioRequest;
use App\Services\Nomina\CuestionarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CuestionarioRespuestaController extends Controller
{
    public function __construct(
        private readonly CuestionarioService $cuestionarioService
    ) {}

    public function pendiente(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->cuestionarioService->pendienteParaUsuario($request->user()->id),
        ]);
    }

    public function show(Request $request, string $convocatoria): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->cuestionarioService->paraPostulante($convocatoria, $request->user()->id),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 403);
        }
    }

    public function store(EnviarRespuestasCuestionarioRequest $request, string $cuestionario): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => '¡Respuestas enviadas correctamente!',
                'data' => $this->cuestionarioService->guardarRespuestas(
                    $cuestionario,
                    $request->user()->id,
                    $request->validated('respuestas')
                ),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }
    }
}
