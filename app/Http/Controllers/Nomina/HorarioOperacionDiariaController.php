<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\GuardarHorarioOperacionDiariaRequest;
use App\Services\Nomina\HorarioOperacionDiariaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HorarioOperacionDiariaController extends Controller
{
    public function __construct(
        private readonly HorarioOperacionDiariaService $horarioOperacionDiariaService
    ) {}

    public function show(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->horarioOperacionDiariaService->porFecha($request->query('fecha')),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener instrucción operativa diaria', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener la instrucción del día.'], 500);
        }
    }

    public function update(GuardarHorarioOperacionDiariaRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Instrucción operativa del día guardada correctamente.',
                'data' => $this->horarioOperacionDiariaService->guardar($request->validated()),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al guardar instrucción operativa diaria', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al guardar la instrucción del día.'], 500);
        }
    }
}
