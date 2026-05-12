<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreValorRequest;
use App\Http\Requests\Nomina\UpdateValorRequest;
use App\Services\Nomina\ValorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ValorController extends Controller
{
    public function __construct(
        private readonly ValorService $valorService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status'   => $request->query('status'),
                'per_page' => $request->query('per_page', 10),
            ];

            $data = $this->valorService->getAll($filters);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al listar valores de hora', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener los valores.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $data = $this->valorService->getByUuid($uuid);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al obtener valor', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Valor no encontrado.'], 404);
        }
    }

    public function store(StoreValorRequest $request): JsonResponse
    {
        try {
            $data = $this->valorService->store($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Valores de hora creados exitosamente.',
                'data'    => $data,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear valores de hora', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al crear los valores.'], 500);
        }
    }

    public function update(UpdateValorRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->valorService->update($uuid, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Valores de hora actualizados exitosamente.',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar valores de hora', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar los valores.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->valorService->destroy($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Valores de hora eliminados exitosamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar valores de hora', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar los valores.'], 500);
        }
    }
}
