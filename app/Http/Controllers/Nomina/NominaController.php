<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\LiquidarNominaRequest;
use App\Http\Requests\Nomina\StoreNominaRequest;
use App\Http\Requests\Nomina\UpdateNominaRequest;
use App\Models\Nomina\Contratacion;
use App\Models\Nomina\Nomina;
use App\Services\Nomina\NominaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NominaController extends Controller
{
    public function __construct(
        private readonly NominaService $nominaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'user_id'            => $request->query('user_id'),
                'jornada_laboral_id' => $request->query('jornada_laboral_id'),
                'per_page'           => $request->query('per_page', 15),
            ];

            $data = $this->nominaService->getAll($filters);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al listar nóminas', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener las nóminas.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $data = $this->nominaService->getByUuid($uuid);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al obtener nómina', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Nómina no encontrada.'], 404);
        }
    }

    public function store(StoreNominaRequest $request): JsonResponse
    {
        try {
            $data = $this->nominaService->store($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Nómina creada exitosamente.',
                'data'    => $data,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear nómina', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al crear la nómina.'], 500);
        }
    }

    public function update(UpdateNominaRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->nominaService->update($uuid, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Nómina actualizada exitosamente.',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar nómina', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar la nómina.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->nominaService->destroy($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Nómina eliminada exitosamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar nómina', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar la nómina.'], 500);
        }
    }

    /**
     * Calcula y liquida la nómina de un empleado para el período dado.
     * Las horas se obtienen automáticamente de las WorkSessions del período.
     *
     * POST /nomina/nominas/liquidar
     * Body: { user_id, periodo_inicio, periodo_fin, jornada_laboral_id, descuento_id? }
     */
    public function resumen(Request $request): JsonResponse
    {
        try {
            $inicio = $request->query('periodo_inicio');
            $fin    = $request->query('periodo_fin');

            $empleadosActivos = Contratacion::where('status', 1)->count();

            $query = Nomina::query();
            if ($inicio && $fin) {
                $query->where('periodo_inicio', '>=', $inicio)
                      ->where('periodo_fin',    '<=', $fin);
            }

            $nominaBruta      = (float) $query->sum('total_devengado');
            $deducciones      = (float) $query->sum('total_deducciones');
            $nominaNeta       = (float) $query->sum('salario_neto');
            $pagosRealizados  = (float) $query->where('liquidada', true)->sum('salario_neto');

            return response()->json([
                'success' => true,
                'data'    => [
                    'empleados_activos' => $empleadosActivos,
                    'nomina_bruta'      => $nominaBruta,
                    'deducciones'       => $deducciones,
                    'nomina_neta'       => $nominaNeta,
                    'pagos_realizados'  => $pagosRealizados,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener resumen de nómina', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener el resumen.'], 500);
        }
    }

    public function liquidar(LiquidarNominaRequest $request): JsonResponse
    {
        try {
            $nomina = $this->nominaService->liquidar($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Nómina liquidada exitosamente.',
                'data'    => $nomina,
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró contrato activo o configuración de tarifas para el empleado.',
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al liquidar nómina', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al liquidar la nómina.'], 500);
        }
    }

    public function desprendible($uuid)
    {
        $nomina = Nomina::with([
            'empleado',
            'contratacion.empresa',
            'descuento',
        ])->where('uuid', $uuid)->firstOrFail();

        $pdf = Pdf::loadView('pdf.desprendible_pago', [
            'nomina'  => $nomina,
            'empresa' => $nomina->contratacion?->empresa,
        ])->setPaper('letter', 'portrait');

        return $pdf->download("desprendible_{$nomina->uuid}.pdf");
    }
}
