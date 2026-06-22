<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StorefirmaTalentoHumanoRequest;
use App\Http\Requests\Nomina\UpdateConfiguracionNominaRequest;
use App\Services\Nomina\ConfiguracionNominaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConfiguracionNominaController extends Controller
{
    public function __construct(
        private readonly ConfiguracionNominaService $configuracionNominaService
    ) {}

    public function show(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->configuracionNominaService->actual(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener configuración de nómina', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener la configuración de nómina.'], 500);
        }
    }

    public function update(UpdateConfiguracionNominaRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Configuración de nómina actualizada correctamente.',
                'data' => $this->configuracionNominaService->guardar($request->validated()),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al guardar configuración de nómina', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al guardar la configuración de nómina.'], 500);
        }
    }

    public function subirFirma(StorefirmaTalentoHumanoRequest $request): JsonResponse
    {
       

        try {
            return response()->json([
                'success' => true,
                'message' => 'Firma de Talento Humano guardada correctamente.',
                'data' => $this->configuracionNominaService->guardarFirma($request->file('firma')),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al guardar firma de Talento Humano', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al guardar la firma de Talento Humano.'], 500);
        }
    }
}
