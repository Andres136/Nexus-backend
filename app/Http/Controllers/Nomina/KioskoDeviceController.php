<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreKioskoDeviceRequest;
use App\Http\Requests\Nomina\UpdateKioskoDeviceRequest;
use App\Services\Nomina\KioskoDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KioskoDeviceController extends Controller
{
    public function __construct(
        private readonly KioskoDeviceService $kioskoDeviceService  // ← agregado readonly
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->kioskoDeviceService->getAll([
                'search'   => $request->query('search'),
                'sede_id'  => auth()->user()->sede_id,
                'per_page' => $request->query('per_page', 10),
            ]);

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

    public function generateActivationLink(string $uuid): JsonResponse
    {
        try {
            $data = $this->kioskoDeviceService->generateActivationLink($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Link de activación generado correctamente.',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function activateDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'fingerprint' => 'required|string|min:20',
        ]);

        try {
            $data = $this->kioskoDeviceService->activateDevice($validated, $request->ip());

            return response()->json([
                'success' => true,
                'message' => 'Kiosko activado correctamente.',
                'data' => $data,
            ], 200);
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function validateSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => 'required|uuid',
            'session_token' => 'required|string',
            'fingerprint' => 'required|string|min:20',
        ]);

        try {
            $device = $this->kioskoDeviceService->validateDeviceSession($validated, $request->ip());

            return response()->json([
                'success' => true,
                'data' => $device,
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function bootstrap(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => 'required|uuid',
            'session_token' => 'required|string',
            'fingerprint' => 'required|string|min:20',
        ]);

        try {
            $data = $this->kioskoDeviceService->bootstrapDeviceSession($validated, $request->ip());

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function generateGuestLink(string $uuid): JsonResponse
    {
        try {
            $data = $this->kioskoDeviceService->generateGuestLink($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Link de acceso temporal generado correctamente.',
                'data'    => $data,
            ], 200);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function bootstrapGuest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid'        => 'required|uuid',
            'guest_token' => 'required|string',
        ]);

        try {
            $data = $this->kioskoDeviceService->bootstrapGuestSession($validated);

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function revoke(string $uuid): JsonResponse
    {
        try {
            $data = $this->kioskoDeviceService->revoke($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Kiosko revocado correctamente.',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function deactivate(string $uuid): JsonResponse
    {
        try {
            $data = $this->kioskoDeviceService->setActiveStatus($uuid, false);

            return response()->json([
                'success' => true,
                'message' => 'Kiosko desactivado correctamente.',
                'data' => $data,
            ], 200);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function activateAdmin(string $uuid): JsonResponse
    {
        try {
            $data = $this->kioskoDeviceService->setActiveStatus($uuid, true);

            return response()->json([
                'success' => true,
                'message' => 'Kiosko activado correctamente.',
                'data' => $data,
            ], 200);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
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
