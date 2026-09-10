<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\DetectarAlmuerzoOmitidoRequest;
use App\Http\Requests\Nomina\GenerarHoraExtraAlmuerzoRequest;
use App\Services\Nomina\AlmuerzoOmitidoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AlmuerzoOmitidoController extends Controller
{
    public function __construct(
        private readonly AlmuerzoOmitidoService $service
    ) {}

    public function index(DetectarAlmuerzoOmitidoRequest $request): JsonResponse
    {
        try {
            $data = $this->service->detectar($request->validated());

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al detectar almuerzos no tomados', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al detectar los almuerzos no tomados.'], 500);
        }
    }

    public function generar(GenerarHoraExtraAlmuerzoRequest $request): JsonResponse
    {
        try {
            $registros = $this->service->generarSolicitudes($request->validated());

            return response()->json([
                'success' => true,
                'message' => $registros->isEmpty()
                    ? 'No se generó ninguna solicitud (los días seleccionados ya tienen hora extra o no aplican).'
                    : "Se generaron {$registros->count()} solicitud(es) de hora extra pendientes de aprobación.",
                'data' => [
                    'creadas' => $registros->count(),
                    'solicitudes' => $registros->map(fn ($h) => [
                        'uuid'        => $h->uuid,
                        'fecha'       => $h->fecha->toDateString(),
                        'hora_inicio' => $h->hora_inicio,
                        'hora_fin'    => $h->hora_fin,
                        'horas'       => $h->horas,
                        'status'      => $h->status,
                    ])->values(),
                ],
            ], $registros->isEmpty() ? 422 : 201);
        } catch (\Exception $e) {
            Log::error('Error al generar horas extra desde almuerzos no tomados', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al generar las solicitudes de hora extra.'], 500);
        }
    }
}
