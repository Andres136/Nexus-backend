<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\LiquidarPrestacionRequest;
use App\Services\Nomina\LiquidacionPrestacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiquidacionPrestacionController extends Controller
{
    public function __construct(
        private readonly LiquidacionPrestacionService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->getAll([
                    'user_id'  => $request->query('user_id'),
                    'tipo'     => $request->query('tipo'),
                    'anio'     => $request->query('anio'),
                    'search'   => $request->query('search'),
                    'per_page' => $request->query('per_page', 15),
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al listar liquidaciones de prestaciones', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener las liquidaciones.'], 500);
        }
    }

    public function tipos(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => LiquidacionPrestacionService::tipos(),
        ]);
    }

    public function preliquidar(LiquidarPrestacionRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Preliquidación calculada.',
                'data'    => $this->service->preliquidar($request->validated()),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'No se encontró un contrato activo para el empleado.'], 422);
        } catch (\LogicException|\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al preliquidar prestación', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al calcular la prestación.'], 500);
        }
    }

    public function liquidar(LiquidarPrestacionRequest $request): JsonResponse
    {
        try {
            $liquidacion = $this->service->liquidar($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Prestación liquidada y registrada exitosamente.',
                'data'    => $liquidacion,
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'No se encontró un contrato activo para el empleado.'], 422);
        } catch (\LogicException|\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al liquidar prestación', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al registrar la prestación.'], 500);
        }
    }
}
