<?php

namespace App\Http\Controllers\Nomina;

use App\Exports\GenericExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\UpdateNominaConceptoContableRequest;
use App\Services\Nomina\NominaConceptoContableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class NominaConceptoContableController extends Controller
{
    public function __construct(
        private readonly NominaConceptoContableService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'tipo' => $request->query('tipo'),
                'activo' => $request->query('activo'),
                'sin_cuenta' => $request->query('sin_cuenta'),
                'search' => $request->query('search'),
                'per_page' => $request->query('per_page', 50),
            ];

            return response()->json([
                'success' => true,
                'data' => $this->service->getAll($filters),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al listar conceptos contables de nómina', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los conceptos contables de nómina.',
            ], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->getByUuid($uuid),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Concepto contable de nómina no encontrado.',
            ], 404);
        }
    }

    public function sincronizarPuc(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Sincronización PUC de nómina completada.',
                'data' => $this->service->sincronizarPuc(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al sincronizar conceptos contables de nómina con PUC', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar los conceptos contables con el PUC.',
            ], 500);
        }
    }

    public function plantillaPucFaltante()
    {
        try {
            $rows = $this->service->plantillaPucFaltante();

            return Excel::download(
                new GenericExport($rows, ['codigo', 'nombre', 'naturaleza', 'descripcion', 'dinamica']),
                'plantilla_puc_nomina_faltante.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('Error al generar plantilla PUC faltante de nómina', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar la plantilla PUC faltante de nómina.',
            ], 500);
        }
    }

    public function update(UpdateNominaConceptoContableRequest $request, string $uuid): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Concepto contable actualizado correctamente.',
                'data' => $this->service->update($uuid, $request->validated()),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar concepto contable de nómina', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el concepto contable de nómina.',
            ], 500);
        }
    }
}
