<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreJornadaLaboralRequest;
use App\Http\Requests\Nomina\UpdateJornadaLaboralRequest;
use App\Services\Nomina\JornadaLaboralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JornadaLaboralController extends Controller
{
    public function __construct(
        private readonly JornadaLaboralService $jornadaLaboralService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search'   => $request->query('search'),
                'status'   => $request->query('status'),
                'per_page' => $request->query('per_page', 10),
            ];

            $data = $this->jornadaLaboralService->getAll($filters);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al listar jornadas laborales', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener las jornadas laborales.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $data = $this->jornadaLaboralService->getByUuid($uuid);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al obtener jornada laboral', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Jornada laboral no encontrada.'], 404);
        }
    }

    public function store(StoreJornadaLaboralRequest $request): JsonResponse
    {
        try {
            $data = $this->jornadaLaboralService->store($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Jornada laboral creada exitosamente.',
                'data'    => $data,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear jornada laboral', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al crear la jornada laboral.'], 500);
        }
    }

    public function update(UpdateJornadaLaboralRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->jornadaLaboralService->update($uuid, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Jornada laboral actualizada exitosamente.',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar jornada laboral', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar la jornada laboral.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->jornadaLaboralService->destroy($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Jornada laboral eliminada exitosamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar jornada laboral', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar la jornada laboral.'], 500);
        }
    }
}
