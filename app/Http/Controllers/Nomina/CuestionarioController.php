<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\CalificarRespuestaCuestionarioRequest;
use App\Http\Requests\Nomina\StoreCuestionarioRequest;
use App\Services\Nomina\CuestionarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CuestionarioController extends Controller
{
    public function __construct(
        private readonly CuestionarioService $cuestionarioService
    ) {}

    public function show(string $convocatoria): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->cuestionarioService->paraConvocatoria($convocatoria),
        ]);
    }

    public function store(StoreCuestionarioRequest $request, string $convocatoria): JsonResponse
    {
        try {
            $cuestionario = $this->cuestionarioService->guardar(
                $convocatoria,
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Cuestionario guardado correctamente.',
                'data' => $cuestionario,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Exception $e) {
            Log::error('Error al guardar cuestionario', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al guardar el cuestionario.'], 500);
        }
    }

    public function publicar(string $uuid): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Cuestionario publicado. Los postulantes ya pueden responder.',
                'data' => $this->cuestionarioService->publicar($uuid),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Exception $e) {
            Log::error('Error al publicar cuestionario', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al publicar el cuestionario.'], 500);
        }
    }

    public function cerrar(string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Cuestionario cerrado.',
            'data' => $this->cuestionarioService->cerrar($uuid),
        ]);
    }

    public function despublicar(string $uuid): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Cuestionario vuelto a borrador. Los postulantes ya no pueden verlo hasta que lo publiques de nuevo.',
                'data' => $this->cuestionarioService->despublicar($uuid),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }
    }

    public function respuestas(string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->cuestionarioService->respuestasPorPostulante($uuid),
        ]);
    }

    public function respuestasUsuario(string $uuid, int $userId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->cuestionarioService->respuestasDeUsuario($uuid, $userId),
        ]);
    }

    public function calificar(CalificarRespuestaCuestionarioRequest $request, int $respuesta): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Calificación guardada.',
            'data' => $this->cuestionarioService->calificar(
                $respuesta,
                $request->validated('calificacion'),
                $request->user()->id
            ),
        ]);
    }
}
