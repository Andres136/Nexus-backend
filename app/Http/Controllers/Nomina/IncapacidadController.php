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
// CONTROLLER
public function store(StoreIncapacidadRequest $request): JsonResponse
{
    try {
        $incapacidad = $this->incapacidadService->store(
            $request->validated(),
            $request->file('soporte')
        );

        return response()->json([
            'success' => true,
            'message' => 'Incapacidad creada exitosamente',
          //  'data'    => $incapacidad,
        ], 201);

    } catch (\Exception $e) {
        return $this->errorResponse($e);
    }
}

    // PUT /incapacidades/{uuid}
 public function update(UpdateIncapacidadRequest $request, string $uuid): JsonResponse
{
    try {
        $incapacidad = $this->incapacidadService->update(
            $uuid,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Incapacidad actualizada correctamente',
            'data' => $incapacidad,
        ]);

    } catch (\Exception $e) {
        return $this->errorResponse($e);
    }
}

    // PATCH /incapacidades/{uuid}/revisar
    public function revisar(string $uuid): JsonResponse
    {
        try {
            $incapacidad = $this->incapacidadService->revisar($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Incapacidad revisada correctamente',
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