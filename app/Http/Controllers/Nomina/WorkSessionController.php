<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreWorkSessionRequest;
use App\Http\Requests\Nomina\UpdateWorkSessionRequest;
use App\Services\Nomina\KioskoDeviceService;
use App\Services\Nomina\WorkSessionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WorkSessionController extends Controller
{
    public function __construct(
        private readonly WorkSessionService $workSessionService,
        private readonly KioskoDeviceService $kioskoDeviceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'user_id'      => $request->query('user_id'),
                'fecha'        => $request->query('fecha'),
                'fecha_inicio' => $request->query('fecha_inicio'),
                'fecha_fin'    => $request->query('fecha_fin'),
                'search'       => $request->query('search'),
                'sede_id'      => $request->query('sede_id'),
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
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Marcación no permitida.',
                'errors' => $e->errors(),
            ], 422);
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

    public function kioskIndex(Request $request): JsonResponse
    {
        try {
            $this->validateKioskRequest($request);

            $filters = [
                'user_id' => $request->query('user_id'),
                'fecha' => $this->kioskNow()->toDateString(),
                'per_page' => $request->query('per_page', 1),
            ];

            $data = $this->workSessionService->getAll($filters);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Error al listar sesión desde kiosko', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener la sesión.'], 500);
        }
    }

    public function kioskStore(StoreWorkSessionRequest $request): JsonResponse
    {
        try {
            $device = $this->validateKioskRequest($request);
            $ahora = $this->kioskNow();
            $data = $request->validated();
            $data['kiosko_id'] = $device->id;
            $data['registro_diario'] = $ahora->toDateString();
            $data['hora_entrada'] = $ahora->toDateTimeString();
            $session = $this->workSessionService->store($data, true);

            return response()->json([
                'success' => true,
                'message' => 'Sesión de trabajo creada exitosamente.',
                'data' => $session,
            ], 201);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Marcación no permitida.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear sesión desde kiosko', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al crear la sesión.'], 500);
        }
    }

    public function kioskUpdate(UpdateWorkSessionRequest $request, string $uuid): JsonResponse
    {
        try {
            $device = $this->validateKioskRequest($request);
            $sessionActual = $this->workSessionService->getByUuid($uuid);

            if ((int) $sessionActual->kiosko_id !== (int) $device->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sesión no pertenece a este kiosko.',
                ], 403);
            }

            $data = $this->aplicarHoraServidorKiosko($request->validated());
            $data = $this->workSessionService->update($uuid, $data, true);

            return response()->json([
                'success' => true,
                'message' => 'Sesión de trabajo actualizada exitosamente.',
                'data' => $data,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Marcación no permitida.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar sesión desde kiosko', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar la sesión.'], 500);
        }
    }

    private function validateKioskRequest(Request $request)
    {
        return $this->kioskoDeviceService->validateDeviceSession([
            'uuid' => (string) $request->header('X-Kiosko-Device'),
            'session_token' => (string) $request->header('X-Kiosko-Session'),
            'fingerprint' => (string) $request->header('X-Kiosko-Fingerprint'),
        ], $request->ip());
    }

    private function aplicarHoraServidorKiosko(array $data): array
    {
        $camposMarcacion = [
            'hora_salida_brake',
            'hora_ingreso_brake',
            'hora_salida_almuerzo',
            'hora_ingreso_almuerzo',
            'hora_salida',
        ];

        $ahora = $this->kioskNow()->toDateTimeString();

        foreach ($camposMarcacion as $campo) {
            if (array_key_exists($campo, $data) && $data[$campo] !== null) {
                $data[$campo] = $ahora;
            }
        }

        unset($data['hora_entrada']);

        return $data;
    }

    private function kioskNow(): Carbon
    {
        return Carbon::now(config('app.timezone'));
    }
}
