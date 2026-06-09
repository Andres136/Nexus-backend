<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\LiquidarRetiroRequest;
use App\Services\Nomina\LiquidacionRetiroService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiquidacionRetiroController extends Controller
{
    public function __construct(
        private readonly LiquidacionRetiroService $liquidacionRetiroService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->liquidacionRetiroService->getAll([
                    'user_id' => $request->query('user_id'),
                    'fecha_desde' => $request->query('fecha_desde'),
                    'fecha_hasta' => $request->query('fecha_hasta'),
                    'search' => $request->query('search'),
                    'per_page' => $request->query('per_page', 20),
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al listar liquidaciones de retiro', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener las liquidaciones definitivas.'], 500);
        }
    }

    public function preliquidar(LiquidarRetiroRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Preliquidación de retiro calculada.',
                'data' => $this->liquidacionRetiroService->preliquidar($request->validated()),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'No se encontró un contrato activo para el empleado.'], 422);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al preliquidar retiro', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al calcular la liquidación de retiro.'], 500);
        }
    }

    public function liquidar(LiquidarRetiroRequest $request): JsonResponse
    {
        try {
            $liquidacion = $this->liquidacionRetiroService->liquidar($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Liquidación definitiva registrada y contrato finalizado.',
                'data' => $liquidacion,
                'advertencias' => $liquidacion->detalle_calculo['advertencias'] ?? [],
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'No se encontró un contrato activo para el empleado.'], 422);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al liquidar retiro', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al registrar la liquidación definitiva.'], 500);
        }
    }

    public function pdf(string $uuid)
    {
        $liquidacion = $this->liquidacionRetiroService->getByUuid($uuid);

        return Pdf::loadView('pdf.liquidacion_retiro', [
            'liquidacion' => $liquidacion,
            'empresa' => $liquidacion->contratacion?->empresa,
        ])->setPaper('letter', 'portrait')
            ->download("liquidacion_retiro_{$liquidacion->uuid}.pdf");
    }
}
