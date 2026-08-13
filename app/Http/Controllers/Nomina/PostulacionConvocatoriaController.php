<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StorePostulacionConvocatoriaRequest;
use App\Services\Nomina\ConvocatoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostulacionConvocatoriaController extends Controller
{
    public function __construct(
        private readonly ConvocatoriaService $convocatoriaService
    ) {}

    public function store(StorePostulacionConvocatoriaRequest $request, string $convocatoria): JsonResponse
    {
        try {
            $postulacion = $this->convocatoriaService->postularse(
                $convocatoria,
                $request->user()->id,
                $request->validated('cargo_interes')
            );

            return response()->json([
                'success' => true,
                'message' => 'Postulación registrada correctamente.',
                'data' => $postulacion,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al registrar postulación', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al registrar la postulación.'], 500);
        }
    }

    public function mine(Request $request, string $convocatoria): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->convocatoriaService->miPostulacion($convocatoria, $request->user()->id),
        ]);
    }

    public function index(Request $request, string $convocatoria): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->convocatoriaService->listarPostulantes($convocatoria, [
                'per_page' => $request->query('per_page', 15),
            ]),
        ]);
    }
}
