<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreKioskoDeviceRequest;
use App\Http\Requests\Nomina\UpdateKioskoDeviceRequest;
use App\Services\Nomina\KioskoDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class KioskoDeviceController extends Controller
{
    public function __construct(
        private readonly KioskoDeviceService $kioskoDeviceService  // ← agregado readonly
    ) {}

    public function index(): JsonResponse
    {
        try {
            $data = $this->kioskoDeviceService->getAll();

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function show(string $uuid): JsonResponse               // ← int $id → string $uuid
    {
        try {
            $data = $this->kioskoDeviceService->getByUuid($uuid);  // ← getById → getByUuid

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function store(StoreKioskoDeviceRequest $request): JsonResponse
    {
        try {
            $data = $this->kioskoDeviceService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Dispositivo kiosko creado correctamente.',
                'data'    => $data,
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function update(UpdateKioskoDeviceRequest $request, string $uuid): JsonResponse  // ← int $id → string $uuid
    {
        try {
            $data = $this->kioskoDeviceService->update($uuid, $request->validated());  // ← $id → $uuid

            return response()->json([
                'success' => true,
                'message' => 'Dispositivo kiosko actualizado correctamente.',
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function destroy(string $uuid): JsonResponse            // ← int $id → string $uuid
    {
        try {
            $this->kioskoDeviceService->delete($uuid);             // ← $id → $uuid

            return response()->json([
                'success' => true,
                'message' => 'Dispositivo kiosko eliminado correctamente.',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(\Exception $e): JsonResponse    // ← método nuevo
    {
        Log::error('Error en KioskoDeviceController', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado',
        ], 500);
    }
}
