<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreAjusteSalarialContratacionRequest;
use App\Services\Nomina\AjusteSalarialContratacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AjusteSalarialContratacionController extends Controller
{
    public function __construct(
        private readonly AjusteSalarialContratacionService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->getAll($request->all()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al listar ajustes salariales', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener los ajustes salariales.'], 500);
        }
    }

    public function store(StoreAjusteSalarialContratacionRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Ajuste salarial registrado correctamente.',
                'data' => $this->service->store($request->validated()),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Error al registrar ajuste salarial', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getByUuid($uuid),
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->destroy($uuid);

            return response()->json(['success' => true, 'message' => 'Ajuste salarial eliminado correctamente.']);
        } catch (\Throwable $e) {
            Log::error('Error al eliminar ajuste salarial', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
