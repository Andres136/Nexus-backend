<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\GestionComisionRequest;
use App\Http\Requests\Nomina\StoreComisionRequest;
use App\Services\Nomina\ComisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ComisionController extends Controller
{
    public function __construct(
        private readonly ComisionService $comisionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'user_id' => $request->query('user_id'),
                'status' => $request->query('status'),
                'periodo_inicio' => $request->query('periodo_inicio'),
                'periodo_fin' => $request->query('periodo_fin'),
                'search' => $request->query('search'),
                'per_page' => $request->query('per_page', 15),
            ];

            return response()->json([
                'success' => true,
                'data' => $this->comisionService->getAll($filters),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al listar comisiones', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener las comisiones.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->comisionService->getByUuid($uuid)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Comisión no encontrada.'], 404);
        }
    }

    public function store(StoreComisionRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Comisión registrada exitosamente.',
                'data' => $this->comisionService->store($request->validated()),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al registrar comisión', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al registrar la comisión.'], 500);
        }
    }

    public function aprobar(GestionComisionRequest $request, string $uuid): JsonResponse
    {
        return $this->gestionar($uuid, 'aprobar', $request->input('observacion'));
    }

    public function rechazar(GestionComisionRequest $request, string $uuid): JsonResponse
    {
        return $this->gestionar($uuid, 'rechazar', $request->input('observacion'));
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->comisionService->destroy($uuid);

            return response()->json(['success' => true, 'message' => 'Comisión eliminada.']);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al eliminar comisión', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al eliminar la comisión.'], 500);
        }
    }

    private function gestionar(string $uuid, string $accion, ?string $observacion): JsonResponse
    {
        try {
            $comision = $this->comisionService->{$accion}($uuid, $observacion);

            return response()->json([
                'success' => true,
                'message' => $accion === 'aprobar' ? 'Comisión aprobada.' : 'Comisión rechazada.',
                'data' => $comision,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error("Error al {$accion} comisión", ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al gestionar la comisión.'], 500);
        }
    }
}
