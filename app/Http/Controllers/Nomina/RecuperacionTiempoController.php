<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreRecuperacionTiempoRequest;
use App\Services\Nomina\RecuperacionTiempoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RecuperacionTiempoController extends Controller
{
    public function __construct(
        private readonly RecuperacionTiempoService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->service->getAll([
                'user_id' => $request->query('user_id'),
                'status' => $request->query('status'),
                'fecha_inicio' => $request->query('fecha_inicio'),
                'fecha_fin' => $request->query('fecha_fin'),
                'per_page' => $request->query('per_page', 15),
            ]);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al listar recuperaciones de tiempo', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener las recuperaciones.'], 500);
        }
    }

    public function store(StoreRecuperacionTiempoRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Recuperación de tiempo autorizada correctamente.',
                'data' => $this->service->store($request->validated()),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al registrar recuperación de tiempo', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al autorizar la recuperación.'], 500);
        }
    }

    public function anular(string $uuid): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Recuperación de tiempo anulada correctamente.',
                'data' => $this->service->anular($uuid),
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al anular recuperación de tiempo', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al anular la recuperación.'], 500);
        }
    }
}
