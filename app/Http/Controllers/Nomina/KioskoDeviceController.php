<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreKioskoDeviceRequest;
use App\Http\Requests\Nomina\UpdateKioskoDeviceRequest;
use App\Services\Nomina\KioskoDeviceService;
use Illuminate\Http\JsonResponse;

class KioskoDeviceController extends Controller
{
    public function __construct(
        private KioskoDeviceService $kioskoDeviceService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->kioskoDeviceService->getAll();
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->kioskoDeviceService->getById($id);
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(StoreKioskoDeviceRequest $request): JsonResponse
    {
        $data = $this->kioskoDeviceService->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Dispositivo kiosko creado correctamente.',
            'data'    => $data,
        ], 201);
    }

    public function update(UpdateKioskoDeviceRequest $request, int $id): JsonResponse
    {
        $data = $this->kioskoDeviceService->update($id, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Dispositivo kiosko actualizado correctamente.',
            'data'    => $data,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->kioskoDeviceService->delete($id);
        return response()->json([
            'success' => true,
            'message' => 'Dispositivo kiosko eliminado correctamente.',
        ]);
    }
}
