<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreHorarioLaboralRequest;
use App\Http\Requests\Nomina\UpdateHorarioLaboralRequest;
use App\Services\Nomina\HorarioLaboralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HorarioLaboralController extends Controller
{
    public function __construct(
        private readonly HorarioLaboralService $horarioLaboralService  // ← agregado readonly
    ) {}

    public function index(): JsonResponse
    {
        try {
            $data = $this->horarioLaboralService->getAll();

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function show(string $uuid): JsonResponse  // ← int $id → string $uuid
    {
        try {
            $data = $this->horarioLaboralService->getByUuid($uuid);  // ← getById → getByUuid

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function store(StoreHorarioLaboralRequest $request): JsonResponse
    {
        try {
            $data = $this->horarioLaboralService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Horario laboral creado correctamente.',
                'data'    => $data,
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function update(UpdateHorarioLaboralRequest $request, string $uuid): JsonResponse  // ← int $id → string $uuid
    {
        try {
            $data = $this->horarioLaboralService->update($uuid, $request->validated());  // ← $id → $uuid

            return response()->json([
                'success' => true,
                'message' => 'Horario laboral actualizado correctamente.',
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function destroy(string $uuid): JsonResponse  // ← int $id → string $uuid
    {
        try {
            $this->horarioLaboralService->delete($uuid);  // ← $id → $uuid

            return response()->json([
                'success' => true,
                'message' => 'Horario laboral eliminado correctamente.',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // ← método nuevo igual que en DescuentoController
    private function errorResponse(\Exception $e): JsonResponse
    {
        Log::error('Error en HorarioLaboralController', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado',
        ], 500);
    }
}