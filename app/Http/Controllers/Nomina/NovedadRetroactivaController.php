<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\GestionNovedadRetroactivaRequest;
use App\Http\Requests\Nomina\StoreNovedadRetroactivaRequest;
use App\Services\Nomina\NovedadRetroactivaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NovedadRetroactivaController extends Controller
{
    public function __construct(
        private readonly NovedadRetroactivaService $novedadRetroactivaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'user_id' => $request->query('user_id'),
                'status' => $request->query('status'),
                'tipo' => $request->query('tipo'),
                'periodo_inicio' => $request->query('periodo_inicio'),
                'periodo_fin' => $request->query('periodo_fin'),
                'search' => $request->query('search'),
                'per_page' => $request->query('per_page', 15),
            ];

            return response()->json([
                'success' => true,
                'data' => $this->novedadRetroactivaService->getAll($filters),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al listar novedades retroactivas', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener las novedades retroactivas.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->novedadRetroactivaService->getByUuid($uuid)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Novedad retroactiva no encontrada.'], 404);
        }
    }

    public function store(StoreNovedadRetroactivaRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Novedad retroactiva registrada exitosamente.',
                'data' => $this->novedadRetroactivaService->store($request->validated()),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al registrar novedad retroactiva', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al registrar la novedad retroactiva.'], 500);
        }
    }

    public function aprobar(GestionNovedadRetroactivaRequest $request, string $uuid): JsonResponse
    {
        return $this->gestionar($uuid, 'aprobar', $request->input('observacion'));
    }

    public function rechazar(GestionNovedadRetroactivaRequest $request, string $uuid): JsonResponse
    {
        return $this->gestionar($uuid, 'rechazar', $request->input('observacion'));
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->novedadRetroactivaService->destroy($uuid);

            return response()->json(['success' => true, 'message' => 'Novedad retroactiva eliminada.']);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al eliminar novedad retroactiva', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al eliminar la novedad retroactiva.'], 500);
        }
    }

    private function gestionar(string $uuid, string $accion, ?string $observacion): JsonResponse
    {
        try {
            $novedad = $this->novedadRetroactivaService->{$accion}($uuid, $observacion);

            return response()->json([
                'success' => true,
                'message' => $accion === 'aprobar' ? 'Novedad retroactiva aprobada.' : 'Novedad retroactiva rechazada.',
                'data' => $novedad,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error("Error al {$accion} novedad retroactiva", ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al gestionar la novedad retroactiva.'], 500);
        }
    }
}
