<?php

namespace App\Http\Controllers\Crm;

use App\EstadoEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CorregirEntregaTotalRequest;
use App\Models\Crm\AlistamientoOt;
use App\Models\Crm\OrdenDeTrabajo;
use App\Services\Crm\GestionCarteraService;
use App\Services\Crm\OrdenTrabajoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ordenTrabajoController extends Controller
{
     public function show($id)
    {
        $orden = OrdenDeTrabajo::with([
            'cliente',
            'user',
            'estado',
            'usuarioRevisor',
            'usuarioReviso',
            'usuarioDespachoRevisor',
            'ordenCompra.cliente',
            'ordenCompra.sede',
            'ordenCompra.detalles.product',
            'ordenCompra.detalles.observacionCalidadUsuario:id,name',
            'ordenCompra.detalles.entregaCorrecciones',

        ])
            ->whereHas('ordenCompra', function ($q) {
                $q->whereNot('estado_id', EstadoEnum::INACTIVO->value);
            })
            ->findOrFail($id);

        return response()->json($orden);
    }


    public function generarPDF($id)
    {
          $orden = OrdenDeTrabajo::with([
            'ordenCompra.detalles.product',
            'ordenCompra.estado',
            'cliente',
            'user',
            'entregas.usuario',
            'ordenCompra.empresa' // Cargar la relación con empresa
        ])
              ->whereHas('ordenCompra', function ($q) {
                  $q->whereNot('estado_id', EstadoEnum::INACTIVO->value);
              })
              ->findOrFail($id);

        // Calcular totales
        $detalles = $orden->ordenCompra->detalles->map(function ($d) {
            $d->cantidadEnviada = $d->cantidad_enviada ?? 0;
            $d->faltantes = $d->faltantes ?? 0;
            $d->valor_total = ($d->valor_unitario ?? 0) * ($d->cantidad ?? 0);
            return $d;
        });

        $alistamientos = AlistamientoOt::where('orden_trabajo_id', $orden->id)
            ->with(['producto', 'bodega.sede'])
            ->get();

        $totalKg = $detalles->sum(fn($d) => (float) ($d->cantidad_requerida_kg ?? 0));
        $valorTotal = $orden->ordenCompra->valor_total ?? $detalles->sum('valor_total');

        $pdf = Pdf::loadView('pdf.orden_trabajo', [
            'orden' => $orden,
            'detalles' => $detalles,
            'alistamientos' => $alistamientos,
            'totalKg' => $totalKg,
            'valorTotal' => $valorTotal,
            'observaciones' => $orden->observaciones ?? 'Sin observaciones',
            'empresa' => $orden->ordenCompra->empresa->nombre ?? 'N/A',
            'carteraInfo' => app(GestionCarteraService::class)->resumenCarteraCliente($orden->ordenCompra->cliente_id),
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

// Revisión al momento del despacho: distinta de revisarOrdenTrabajo() (barrido
// diario del dashboard operativo/VSM). Aquí se confirma quién revisó la OT en
// el flujo de esta pantalla (detalle de orden de trabajo), típicamente al
// momento de despachar.
public function marcarDespachoRevisado($id)
{
    $orden = OrdenDeTrabajo::findOrFail($id);

    $orden->update([
        'despacho_revisado_at'  => now(),
        'despacho_revisado_por' => auth()->id(),
    ]);

    return response()->json([
        'message' => 'Orden de trabajo revisada para despacho',
        'orden'   => $orden,
    ]);
}

//Funcion revisar orden de trabajo
public function revisarOrdenTrabajo($id)
{
    $orden = OrdenDeTrabajo::findOrFail($id);

    // Siempre refresca revisada_at: el usuario hace un barrido diario y necesita
    // que reconfirmar hoy una orden ya revisada actualice la fecha, o el barrido
    // de días anteriores nunca se distingue del de hoy.
    $orden->update([
        'revisada' => true,
        'revisada_por' => auth()->id(),
        'revisada_at' => now(),
    ]);

    return response()->json([
        'message' => 'Orden de trabajo marcada como revisada',
        'orden'   => $orden
    ]);
}

/**
 * Corregir el total enviado de un detalle de la OT (cuando alguien se equivocó).
 * Deja registro en el historial de correcciones de quién lo hizo y por qué.
 */
public function corregirTotalEnviado(CorregirEntregaTotalRequest $request, $id, $detalleId, OrdenTrabajoService $service)
{
    try {
        $resultado = $service->corregirTotalEnviado(
            (int) $id,
            (int) $detalleId,
            (float) $request->validated()['cantidad_total'],
            $request->validated()['motivo'],
            auth()->id()
        );

        return response()->json([
            'message' => 'Total enviado corregido correctamente',
            'correccion' => $resultado['correccion'],
            'detalle' => $resultado['detalle'],
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'No se pudo corregir el total enviado.',
            'errors' => $e->errors(),
        ], 422);
    }
}

    }
