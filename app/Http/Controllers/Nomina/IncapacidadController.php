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
    public function __construct(
        private readonly IncapacidadService $incapacidadService
    ) {}

    // GET /incapacidades
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

    // GET /incapacidades/{uuid}
    public function show(string $uuid): JsonResponse          // ← int $id → string $uuid
    {
        try {
            $incapacidad = $this->incapacidadService->getByUuid($uuid);  // ← getById → getByUuid

            return response()->json([
                'success' => true,
                'data'    => $incapacidad,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // POST /incapacidades
    public function store(StoreIncapacidadRequest $request): JsonResponse
    {
        try {
            $incapacidad = $this->incapacidadService->store($request);

            return response()->json([
                'success' => true,
                'message' => 'Incapacidad creada exitosamente',
                'data'    => $incapacidad,
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // PUT /incapacidades/{uuid}
    public function update(UpdateIncapacidadRequest $request, string $uuid): JsonResponse  // ← int $id → string $uuid
    {
        try {
            $incapacidad = $this->incapacidadService->update($request, $uuid);  // ← $id → $uuid

            return response()->json([
                'success' => true,
                'message' => 'Incapacidad actualizada exitosamente',
                'data'    => $incapacidad,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // DELETE /incapacidades/{uuid}
    public function destroy(string $uuid): JsonResponse       // ← int $id → string $uuid
    {
        try {
            $this->incapacidadService->destroy($uuid);        // ← $id → $uuid

            return response()->json([
                'success' => true,
                'message' => 'Incapacidad eliminada exitosamente',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

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