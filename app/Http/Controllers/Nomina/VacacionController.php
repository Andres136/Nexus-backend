<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\GestionVacacionRequest;
use App\Http\Requests\Nomina\StoreVacacionRequest;
use App\Services\Nomina\VacacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VacacionController extends Controller
{
    public function __construct(
        private readonly VacacionService $vacacionService
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

            return response()->json(['success' => true, 'data' => $this->vacacionService->getAll($filters)]);
        } catch (\Exception $e) {
            Log::error('Error al listar vacaciones', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener las vacaciones.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->vacacionService->getByUuid($uuid)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Vacación no encontrada.'], 404);
        }
    }

    public function store(StoreVacacionRequest $request): JsonResponse
    {
        try {
            $vacacion = $this->vacacionService->store($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Vacación registrada exitosamente.',
                'data'    => $vacacion,
            ], 201);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al registrar vacación', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al registrar la vacación.'], 500);
        }
    }

    public function aprobar(GestionVacacionRequest $request, string $uuid): JsonResponse
    {
        try {
            $vacacion = $this->vacacionService->aprobar($uuid, $request->input('observacion'));

            return response()->json([
                'success' => true,
                'message' => 'Vacación aprobada.',
                'data'    => $vacacion,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al aprobar vacación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al aprobar la vacación.'], 500);
        }
    }

    public function rechazar(GestionVacacionRequest $request, string $uuid): JsonResponse
    {
        try {
            $vacacion = $this->vacacionService->rechazar($uuid, $request->input('observacion'));

            return response()->json([
                'success' => true,
                'message' => 'Vacación rechazada.',
                'data'    => $vacacion,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al rechazar vacación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al rechazar la vacación.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->vacacionService->destroy($uuid);

            return response()->json(['success' => true, 'message' => 'Vacación eliminada.']);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al eliminar vacación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar la vacación.'], 500);
        }
    }
}
