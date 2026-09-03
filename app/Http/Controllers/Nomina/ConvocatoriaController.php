<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreConvocatoriaRequest;
use App\Http\Requests\Nomina\UpdateConvocatoriaRequest;
use App\Services\Nomina\ConvocatoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConvocatoriaController extends Controller
{
    public function __construct(
        private readonly ConvocatoriaService $convocatoriaService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->convocatoriaService->listar(),
        ]);
    }

    public function activa(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->convocatoriaService->activaParaUsuario($request->user()),
        ]);
    }

    public function activas(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->convocatoriaService->activasParaUsuario($request->user()),
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->convocatoriaService->mostrar($uuid),
        ]);
    }

    public function store(StoreConvocatoriaRequest $request): JsonResponse
    {
        try {
            $convocatoria = $this->convocatoriaService->crear(
                $request->validated(),
                $request->file('imagen'),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Convocatoria creada correctamente.',
                'data' => $convocatoria,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear convocatoria', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al crear la convocatoria.'], 500);
        }
    }

    public function update(UpdateConvocatoriaRequest $request, string $uuid): JsonResponse
    {
        try {
            $convocatoria = $this->convocatoriaService->actualizar(
                $uuid,
                $request->validated(),
                $request->file('imagen')
            );

            return response()->json([
                'success' => true,
                'message' => 'Convocatoria actualizada correctamente.',
                'data' => $convocatoria,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar convocatoria', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar la convocatoria.'], 500);
        }
    }

    public function activar(string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Convocatoria activada correctamente.',
            'data' => $this->convocatoriaService->cambiarEstado($uuid, true),
        ]);
    }

    public function desactivar(string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Convocatoria desactivada correctamente.',
            'data' => $this->convocatoriaService->cambiarEstado($uuid, false),
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->convocatoriaService->eliminar($uuid);

        return response()->json([
            'success' => true,
            'message' => 'Convocatoria eliminada correctamente.',
        ]);
    }
}
