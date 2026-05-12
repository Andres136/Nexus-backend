<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreContratacionRequest;
use App\Http\Requests\Nomina\UpdateContratacionRequest;
use App\Services\Nomina\ContratacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContratacionController extends Controller
{
    public function __construct(
        private ContratacionService $contratacionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search'       => $request->query('search'),
                'fecha_inicio' => $request->query('fecha_inicio'),
                'fecha_fin'    => $request->query('fecha_fin'),
                'status'       => $request->query('status'),
                'per_page'     => $request->query('per_page', 10),
            ];

            $data = $this->contratacionService->getAll($filters);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al listar contrataciones', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener las contrataciones.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $data = $this->contratacionService->getByUuid($uuid);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al obtener contratación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Contratación no encontrada.'], 404);
        }
    }

    public function store(StoreContratacionRequest $request): JsonResponse
    {
        try {
            $data = $this->contratacionService->create($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Contratación creada correctamente.',
                'data'    => $data,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear contratación', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al crear la contratación.'], 500);
        }
    }

    public function update(UpdateContratacionRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->contratacionService->update($uuid, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Contratación actualizada correctamente.',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar contratación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar la contratación.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->contratacionService->delete($uuid);
            return response()->json([
                'success' => true,
                'message' => 'Contratación eliminada correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar contratación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar la contratación.'], 500);
        }
    }
}
