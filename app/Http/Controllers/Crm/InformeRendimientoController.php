<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Reportes\InformeRendimientoService;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function preguntar(Request $request)
    {
        $datosReq = $request->validate([
            'anio' => 'nullable|integer|min:2020|max:2100',
            'mes' => 'nullable|integer|between:1,12',
            'pregunta' => 'required|string|max:1000',
            'historial' => 'nullable|array|max:20',
            'historial.*.rol' => 'required_with:historial|in:user,asistente',
            'historial.*.contenido' => 'required_with:historial|string|max:2000',
        ]);

        $anio = $datosReq['anio'] ?? now()->year;
        $mes = $datosReq['mes'] ?? now()->month;
        $agregados = $this->servicio->agregarDatos($anio, $mes);

        try {
            $respuesta = $this->servicio->responderPregunta($agregados, $datosReq['historial'] ?? [], $datosReq['pregunta']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'No se pudo obtener respuesta de la IA en este momento.'], 502);
        }

        return response()->json(['respuesta' => $respuesta]);
    }

    public function exportarPdf(Request $request)
    {
        $datosReq = $request->validate([
            'anio' => 'nullable|integer|min:2020|max:2100',
            'mes' => 'nullable|integer|between:1,12',
            'informe' => 'nullable|string|max:8000',
            'historial' => 'nullable|array|max:20',
            'historial.*.rol' => 'required_with:historial|in:user,asistente',
            'historial.*.contenido' => 'required_with:historial|string|max:2000',
        ]);

        $anio = $datosReq['anio'] ?? now()->year;
        $mes = $datosReq['mes'] ?? now()->month;
        $agregados = $this->servicio->agregarDatos($anio, $mes);

        $informeTexto = $datosReq['informe'] ?? null;
        if (!$informeTexto) {
            try {
                $informeTexto = $this->servicio->generarInformeTexto($agregados);
            } catch (\Throwable $e) {
                report($e);
                $informeTexto = null;
            }
        }

        $pdf = Pdf::loadView('pdf.informe_rendimiento', [
            'periodo' => $agregados['periodo'],
            'datos' => $agregados,
            'informeTexto' => $informeTexto,
            'historialChat' => $datosReq['historial'] ?? [],
            'generadoEn' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download("informe-rendimiento_{$anio}-{$mes}_" . now()->format('Y-m-d_His') . '.pdf');
    }
}
