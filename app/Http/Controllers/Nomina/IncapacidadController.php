<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreIncapacidadRequest;
use App\Http\Requests\Nomina\UpdateIncapacidadRequest;
use App\Services\Nomina\IncapacidadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class IncapacidadController extends Controller
{
    // El Service se inyecta automáticamente — no lo instancias a mano
    // Esto se llama Inyección de Dependencias
    public function __construct(
        private readonly IncapacidadService $incapacidadService
    ) {}

    // =====================
    // GET /incapacidades
    // =====================
    public function index(): JsonResponse
    {
        try {
            $incapacidades = $this->incapacidadService->getAll();

            return response()->json([
                'success' => true,
                'data'    => $incapacidades,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // =====================
    // GET /incapacidades/{id}
    // =====================
    public function show(int $id): JsonResponse
    {
        try {
            $incapacidad = $this->incapacidadService->getById($id);

            return response()->json([
                'success' => true,
                'data'    => $incapacidad,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // =====================
    // POST /incapacidades
    // =====================
    public function store(StoreIncapacidadRequest $request): JsonResponse
    {
        // El Request valida ANTES de llegar aquí
        // Si algo falla en validación, Laravel retorna 422 automáticamente
        try {
            $incapacidad = $this->incapacidadService->store($request);

            return response()->json([
                'success' => true,
                'message' => 'Incapacidad creada exitosamente',
                'data'    => $incapacidad,
            ], 201); // 201 = Created

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // =====================
    // PUT /incapacidades/{id}
    // =====================
    public function update(UpdateIncapacidadRequest $request, int $id): JsonResponse
    {
        try {
            $incapacidad = $this->incapacidadService->update($request, $id);

            return response()->json([
                'success' => true,
                'message' => 'Incapacidad actualizada exitosamente',
                'data'    => $incapacidad,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // =====================
    // DELETE /incapacidades/{id}
    // =====================
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->incapacidadService->destroy($id);

            return response()->json([
                'success' => true,
                'message' => 'Incapacidad eliminada exitosamente',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // =====================
    // Respuesta de error centralizada
    // =====================
    private function errorResponse(\Exception $e): JsonResponse
    {
        Log::error('Error en IncapacidadController', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado',
        ], 500);
    }
}
