<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\GestionHoraExtraRequest;
use App\Http\Requests\Nomina\StoreHoraExtraRequest;
use App\Services\Nomina\HoraExtraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HoraExtraController extends Controller
{
    public function __construct(
        private readonly HoraExtraService $horaExtraService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'user_id'     => $request->query('user_id'),
                'sede_id'     => $request->query('sede_id'),
                'kiosko_device_id' => $request->query('kiosko_device_id'),
                'status'      => $request->query('status'),
                'tipo'        => $request->query('tipo'),
                'search'      => $request->query('search'),
                'mine'        => $request->boolean('mine'),
                'fecha_desde' => $request->query('fecha_desde'),
                'fecha_hasta' => $request->query('fecha_hasta'),
                'per_page'    => $request->query('per_page', 15),
            ];

            $data = $this->horaExtraService->getAll($filters);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al listar horas extras', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener las horas extras.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $data = $this->horaExtraService->getByUuid($uuid);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al obtener hora extra', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Hora extra no encontrada.'], 404);
        }
    }

    /**
     * Crea el registro de horas extras para uno o varios empleados.
     *
     * POST /nomina/horas-extras
     * Body: { users: [1,2,3], fecha, horas, tipo, motivo? }
     */
    public function store(StoreHoraExtraRequest $request): JsonResponse
    {
        try {
            $registros = $this->horaExtraService->store($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Se registraron horas extras para {$registros->count()} empleado(s).",
                'data'    => $registros,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al registrar horas extras', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al registrar las horas extras.'], 500);
        }
    }

    /**
     * PATCH /nomina/horas-extras/{uuid}/aprobar
     */
    public function aprobar(GestionHoraExtraRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->horaExtraService->aprobar($uuid, $request->input('observacion'));

            return response()->json([
                'success' => true,
                'message' => 'Horas extras aprobadas.',
                'data'    => $data,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al aprobar hora extra', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al aprobar la hora extra.'], 500);
        }
    }

    /**
     * PATCH /nomina/horas-extras/{uuid}/rechazar
     */
    public function rechazar(GestionHoraExtraRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->horaExtraService->rechazar($uuid, $request->input('observacion'));

            return response()->json([
                'success' => true,
                'message' => 'Horas extras rechazadas.',
                'data'    => $data,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al rechazar hora extra', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al rechazar la hora extra.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->horaExtraService->destroy($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Hora extra eliminada.',
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al eliminar hora extra', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar la hora extra.'], 500);
        }
    }
}
