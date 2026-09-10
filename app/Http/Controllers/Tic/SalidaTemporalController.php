<?php

namespace App\Http\Controllers\Tic;

use App\Http\Controllers\Controller;
use App\Services\Tic\SalidaTemporalService;
use Illuminate\Http\Request;

class SalidaTemporalController extends Controller
{
    public function __construct(private readonly SalidaTemporalService $service) {}

    public function store(Request $request, int $asignacion)
    {
        $data = $request->validate([
            'motivo' => 'nullable|string|max:2000',
            'fecha_retorno_estimada' => 'nullable|date',
        ]);

        $salida = $this->service->registrarSalida($asignacion, $data, $request->user());

        return response()->json([
            'message' => 'Salida temporal registrada correctamente.',
            'data' => $salida,
        ]);
    }

    public function retorno(Request $request, int $salidaTemporal)
    {
        $data = $request->validate([
            'observaciones_retorno' => 'nullable|string|max:2000',
        ]);

        $salida = $this->service->registrarRetorno($salidaTemporal, $data, $request->user());

        return response()->json([
            'message' => 'Retorno registrado correctamente.',
            'data' => $salida,
        ]);
    }
}
