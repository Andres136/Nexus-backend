<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreTransacionalRegistroRequest;
use App\Http\Requests\Nomina\UpdateTransacionalRegistroRequest;
use App\Services\Nomina\TransacionalRegistroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransacionalRegistroController extends Controller
{
    public function __construct(
        private readonly TransacionalRegistroService $transacionalRegistroService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'users_id' => $request->query('users_id'),
                'fecha'    => $request->query('fecha'),
                'per_page' => $request->query('per_page', 15),
            ];

            $data = $this->transacionalRegistroService->getAll($filters);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al listar marcaciones', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener las marcaciones.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $data = $this->transacionalRegistroService->getByUuid($uuid);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al obtener marcación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Marcación no encontrada.'], 404);
        }
    }

    public function byUser(int $userId): JsonResponse
    {
        try {
            $data = $this->transacionalRegistroService->getByUser($userId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al obtener marcaciones del empleado', ['userId' => $userId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener las marcaciones.'], 500);
        }
    }

    public function store(StoreTransacionalRegistroRequest $request): JsonResponse
    {
        try {
            $data = $this->transacionalRegistroService->store($request);
            return response()->json([
                'success' => true,
                'message' => 'Marcación registrada correctamente.',
                'data'    => $data,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al registrar marcación', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al registrar la marcación.'], 500);
        }
    }

    public function update(UpdateTransacionalRegistroRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->transacionalRegistroService->update($request, $uuid);
            return response()->json([
                'success' => true,
                'message' => 'Marcación actualizada correctamente.',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar marcación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar la marcación.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->transacionalRegistroService->delete($uuid);
            return response()->json([
                'success' => true,
                'message' => 'Marcación eliminada correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar marcación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar la marcación.'], 500);
        }
    }
}
