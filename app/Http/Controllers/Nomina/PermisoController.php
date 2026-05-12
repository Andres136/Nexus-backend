<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\GestionPermisoRequest;
use App\Http\Requests\Nomina\StorePermisoRequest;
use App\Services\Nomina\PermisoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PermisoController extends Controller
{
    public function __construct(
        private readonly PermisoService $permisoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'user_id'     => $request->query('user_id'),
                'status'      => $request->query('status'),
                'tipo'        => $request->query('tipo'),
                'fecha_desde' => $request->query('fecha_desde'),
                'fecha_hasta' => $request->query('fecha_hasta'),
                'per_page'    => $request->query('per_page', 15),
            ];

            return response()->json(['success' => true, 'data' => $this->permisoService->getAll($filters)]);
        } catch (\Exception $e) {
            Log::error('Error al listar permisos', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener los permisos.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->permisoService->getByUuid($uuid)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Permiso no encontrado.'], 404);
        }
    }

    /**
     * POST /nomina/permisos
     * Body: { user_id, fecha, tipo, hora_inicio, hora_fin, es_remunerado, motivo? }
     */
    public function store(StorePermisoRequest $request): JsonResponse
    {
        try {
            $permiso = $this->permisoService->store($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Permiso registrado exitosamente.',
                'data'    => $permiso,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al registrar permiso', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al registrar el permiso.'], 500);
        }
    }

    /**
     * PATCH /nomina/permisos/{uuid}/aprobar
     */
    public function aprobar(GestionPermisoRequest $request, string $uuid): JsonResponse
    {
        try {
            $permiso = $this->permisoService->aprobar($uuid, $request->input('observacion'));

            return response()->json([
                'success' => true,
                'message' => 'Permiso aprobado.',
                'data'    => $permiso,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al aprobar permiso', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al aprobar el permiso.'], 500);
        }
    }

    /**
     * PATCH /nomina/permisos/{uuid}/rechazar
     */
    public function rechazar(GestionPermisoRequest $request, string $uuid): JsonResponse
    {
        try {
            $permiso = $this->permisoService->rechazar($uuid, $request->input('observacion'));

            return response()->json([
                'success' => true,
                'message' => 'Permiso rechazado.',
                'data'    => $permiso,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al rechazar permiso', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al rechazar el permiso.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->permisoService->destroy($uuid);

            return response()->json(['success' => true, 'message' => 'Permiso eliminado.']);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al eliminar permiso', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar el permiso.'], 500);
        }
    }
}
