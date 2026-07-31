<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Reportes\InformeRendimientoService;
use Illuminate\Http\Request;

class InformeRendimientoController extends Controller
{
    public function __construct(private readonly InformeRendimientoService $servicio) {}

    public function generar(Request $request)
    {
        $datos = $request->validate([
            'anio' => 'nullable|integer|min:2020|max:2100',
            'mes' => 'nullable|integer|between:1,12',
        ]);
        $anio = $datos['anio'] ?? now()->year;
        $mes = $datos['mes'] ?? now()->month;

        $agregados = $this->servicio->agregarDatos($anio, $mes);

        try {
            $informe = $this->servicio->generarInformeTexto($agregados);
            $error = null;
        } catch (\Throwable $e) {
            report($e);
            $informe = null;
            $error = 'No se pudo generar el informe con IA. Los datos numéricos siguen disponibles.';
        }

        return response()->json([
            'periodo' => ['anio' => $anio, 'mes' => $mes],
            'datos' => $agregados,
            'informe' => $informe,
            'informe_error' => $error,
        ]);
    }
}
