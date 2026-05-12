<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreWorkSessionRequest;
use App\Http\Requests\Nomina\UpdateWorkSessionRequest;
use App\Services\Nomina\WorkSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkSessionController extends Controller
{
    public function __construct(
        private readonly WorkSessionService $workSessionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'user_id'      => $request->query('user_id'),
                'fecha'        => $request->query('fecha'),
                'fecha_inicio' => $request->query('fecha_inicio'),
                'fecha_fin'    => $request->query('fecha_fin'),
                'per_page'     => $request->query('per_page', 15),
            ];

            $data = $this->workSessionService->getAll($filters);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al listar sesiones de trabajo', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener las sesiones.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $data = $this->workSessionService->getByUuid($uuid);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al obtener sesión', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Sesión no encontrada.'], 404);
        }
    }

    public function store(StoreWorkSessionRequest $request): JsonResponse
    {
        try {
            $data = $this->workSessionService->store($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Sesión de trabajo creada exitosamente.',
                'data'    => $data,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear sesión de trabajo', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al crear la sesión.'], 500);
        }
    }

    public function update(UpdateWorkSessionRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->workSessionService->update($uuid, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Sesión de trabajo actualizada exitosamente.',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar sesión de trabajo', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar la sesión.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->workSessionService->destroy($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Sesión de trabajo eliminada exitosamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar sesión de trabajo', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar la sesión.'], 500);
        }
    }
}
