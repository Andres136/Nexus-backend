<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\OrdenDeTrabajo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ordenTrabajoController extends Controller
{
     public function show($id)
    {
        $orden = OrdenDeTrabajo::with([
            'cliente',
            'user',
            'estado',
            'usuarioRevisor',
            'ordenCompra.detalles.product',

        ])->findOrFail($id);

        return response()->json($orden);
    }


    public function generarPDF($id)
    {
          $orden = OrdenDeTrabajo::with([
            'ordenCompra.detalles.producto',
            'ordenCompra.estado',
            'cliente',
            'user',
            'entregas.usuario',
            'ordenCompra.empresa' // Cargar la relación con empresa
        ])->findOrFail($id);

        // Calcular totales
        $detalles = $orden->ordenCompra->detalles->map(function ($d) {
            $d->cantidadEnviada = $d->cantidad_enviada ?? 0;
            $d->faltantes = $d->faltantes ?? 0;
            $d->valor_total = ($d->valor_unitario ?? 0) * ($d->cantidad ?? 0);
            return $d;
        });

        $totalKg = $detalles->sum(fn($d) => (float) ($d->cantidad_requerida_kg ?? 0));
        $valorTotal = $orden->ordenCompra->valor_total ?? $detalles->sum('valor_total');

        $pdf = Pdf::loadView('pdf.orden_trabajo', [
            'orden' => $orden,
            'detalles' => $detalles,
            'totalKg' => $totalKg,
            'valorTotal' => $valorTotal,
            'observaciones' => $orden->observaciones ?? 'Sin observaciones',
            'empresa' => $orden->empresa->nombre ?? 'N/A',
        ]);

        $fileName = "ordenes_trabajo/orden_trabajo_{$orden->id}.pdf";
        Storage::disk('public')->put($fileName, $pdf->output());

        // Guardar la ruta si quieres persistirla
        $orden->update(['pdf_path' => $fileName]);

        return response()->download(storage_path("app/public/{$fileName}"));
    }


public function marcarRevisada($id)
{
    $orden = OrdenDeTrabajo::findOrFail($id);

    if (!$orden->documento_revisado_at) {
        $orden->update([
            'documento_revisado_at'  => now(),
            'documento_revisado_por' => auth()->id(),
        ]);
    }

    return response()->json([
        'message' => 'Orden de trabajo revisada correctamente',
        'orden'   => $orden
    ]);
}


    }

