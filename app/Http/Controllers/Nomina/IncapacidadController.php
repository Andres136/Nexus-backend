<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreIncapacidadRequest;
use App\Http\Requests\Nomina\UpdateIncapacidadRequest;
use App\Services\Nomina\IncapacidadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IncapacidadController extends Controller
{
    public function __construct(
        private readonly IncapacidadService $incapacidadService
    ) {}

    // GET /incapacidades
    public function index(Request $request): JsonResponse
    {
        try {
            $incapacidades = $this->incapacidadService->getAll(
                $request->only([
                    'search', 'user_id', 'sede_id', 'status', 'estado_revision',
                    'fecha_desde', 'fecha_hasta', 'per_page',
                ])
            );

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

    public function soporte(string $uuid)
    {
        try {
            $incapacidad = $this->incapacidadService->getByUuid($uuid);

            if (!$incapacidad->soporte || !Storage::disk('public')->exists($incapacidad->soporte)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La incapacidad no tiene soporte disponible.',
                ], 404);
            }

            $path = Storage::disk('public')->path($incapacidad->soporte);
            $mime = Storage::disk('public')->mimeType($incapacidad->soporte) ?: 'application/octet-stream';

            return response()->file($path, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            ]);
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
    public function revisar(Request $request, string $uuid): JsonResponse
    {
        try {
            $validated = $request->validate([
                'estado_revision' => 'required|in:aprobada,rechazada',
                'observacion_revision' => 'nullable|string|max:1000',
            ]);

            $incapacidad = $this->incapacidadService->revisar(
                $uuid,
                $validated['estado_revision'],
                $validated['observacion_revision'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => $validated['estado_revision'] === 'aprobada'
                    ? 'Incapacidad aprobada correctamente'
                    : 'Incapacidad rechazada correctamente',
                'data'    => $incapacidad,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de revisión inválidos',
                'errors' => $e->errors(),
            ], 422);
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
