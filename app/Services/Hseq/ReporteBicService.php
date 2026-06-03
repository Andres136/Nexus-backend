<?php

namespace App\Services\Hseq;

use App\Models\Hseq\ReporteBic;
use Illuminate\Support\Facades\Storage;

class ReporteBicService
{
    public function guardarReporte(array $data): ReporteBic
    {
        $reporte = new ReporteBic();

        $reporte->empresa_id = $data['empresa_id'];
        $reporte->nombre = $data['nombre'];
        $reporte->fecha_reporte = $data['fecha_reporte'];

        if (!empty($data['archivo'])) {
            $reporte->archivo_path = $data['archivo']
                ->store('reportes_bic', 'public');
        }

        $reporte->save();

        return $reporte;
    }

    public function editarReporte(string $uuid, array $data): ReporteBic
    {
        $reporte = ReporteBic::where('uuid', $uuid)->firstOrFail();

        $reporte->empresa_id = $data['empresa_id'] ?? $reporte->empresa_id;
        $reporte->nombre = $data['nombre'] ?? $reporte->nombre;
        $reporte->fecha_reporte = $data['fecha_reporte'] ?? $reporte->fecha_reporte;

        if (!empty($data['archivo'])) {

            if ($reporte->archivo_path &&
                Storage::disk('public')->exists($reporte->archivo_path)) {
                Storage::disk('public')->delete($reporte->archivo_path);
            }

            $reporte->archivo_path = $data['archivo']
                ->store('reportes_bic', 'public');
        }

        $reporte->save();

        return $reporte;
    }

    public function obtenerReportePorUuid(string $uuid): ReporteBic
    {
        return ReporteBic::where('uuid', $uuid)->firstOrFail();
    }

    public function obtenerTodosLosReportes()
    {
        return ReporteBic::with('empresa')
            ->latest('fecha_reporte')
            ->get();
    }

    public function eliminarReporte(string $uuid): bool
    {
        $reporte = ReporteBic::where('uuid', $uuid)->firstOrFail();

        if (
            $reporte->archivo_path &&
            Storage::disk('public')->exists($reporte->archivo_path)
        ) {
            Storage::disk('public')->delete($reporte->archivo_path);
        }

        return $reporte->delete();
    }
}