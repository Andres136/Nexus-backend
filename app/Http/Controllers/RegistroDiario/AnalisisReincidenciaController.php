<?php

namespace App\Http\Controllers\RegistroDiario;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroDiario\AnalisisReincidenciaRequest;
use App\Http\Requests\RegistroDiario\VerificarReincidenciaRequest;
use App\Services\RegistroDiario\AnalisisReincidenciaService;

class AnalisisReincidenciaController extends Controller
{
    protected AnalisisReincidenciaService $service;

    public function __construct(AnalisisReincidenciaService $service)
    {
        $this->service = $service;
    }

    public function analizar(AnalisisReincidenciaRequest $request)
    {
        $data = $request->validated();

        try {
            $analisis = $this->service->analizar(
                isset($data['departamento_id']) ? (int) $data['departamento_id'] : null,
                $data['fecha_inicio'] ?? null,
                $data['fecha_fin'] ?? null,
            );

            return response()->json($analisis);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'No se pudo generar el análisis de reincidencia.',
                'error' => $e->getMessage(),
            ], 502);
        }
    }

    public function verificar(VerificarReincidenciaRequest $request)
    {
        $data = $request->validated();

        try {
            $resultado = $this->service->verificarCandidata(
                $data['descripcion'],
                isset($data['departamento_id']) ? (int) $data['departamento_id'] : null,
                $data['causa'] ?? null,
                $data['clasificacion'] ?? 'NO_CONFORMIDAD',
            );

            return response()->json($resultado);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'No se pudo verificar la reincidencia.',
                'error' => $e->getMessage(),
            ], 502);
        }
    }
}
