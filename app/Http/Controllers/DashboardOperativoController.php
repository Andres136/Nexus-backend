<?php

namespace App\Http\Controllers;

use App\Models\Crm\Orden_Compra_Detalle;
use App\Models\Crm\OrdenCompraProveedorDetalleOrigen;
use App\Services\Crm\DhasboardOperativoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DashboardOperativoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $service;
    public function __construct(DhasboardOperativoService $service)
    {
        $this->service = $service;
    }
    public function getPrioridades(Request $request)
    {
        $filters = $request->only(['sede_id', 'proveedor_id', 'solo_pendientes', 'search', 'page', 'per_page']);
        $data = $this->service->getPrioridadesActivas($filters);
        return response()->json($data);
    }

public function index(Request $request)
{
$filters = $request->all();


    $data = $this->service->obtenerOrdenesCompraVSM( $filters);
    return response()->json($data, 200, [], JSON_PRETTY_PRINT);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    public function show($id, Request $request)
    {
        $filters = array_merge($request->all(), ['producto_id' => $id]);
        $data = $this->service->obtenerOrdenesCompraVSM($filters);
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updatePrioridad(Request $request, $id)
    {
        $request->validate(['cantidad_prioridad' => 'required|numeric|min:0']);
        $origen = OrdenCompraProveedorDetalleOrigen::findOrFail($id);
        $origen->update(['cantidad_prioridad' => $request->cantidad_prioridad]);
        return response()->json(['message' => 'Prioridad actualizada correctamente']);
    }

    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * GET /vsm/ordenes-pdf — exporta a PDF las órdenes que cumplan los
     * mismos filtros aplicados en el Dashboard Operativo (index()).
     */
    public function exportarPdf(Request $request)
    {
        $filters = $request->all();
        $ordenes = $this->service->obtenerOrdenesCompraVSM($filters);

        $pdf = Pdf::loadView('pdf.dashboard_operativo', [
            'ordenes' => $ordenes,
            'generadoEn' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('torre-control-vsm_' . now()->format('Y-m-d_His') . '.pdf');
    }

    // Observación por ítem (Orden_Compra_Detalle) en el panel de alistamiento/VSM.
    public function updateObservacionItem(Request $request, $id)
    {
        $request->validate(['observaciones' => 'nullable|string|max:2000']);

        $detalle = Orden_Compra_Detalle::findOrFail($id);
        $detalle->update(['observaciones' => $request->observaciones]);

        return response()->json([
            'message' => 'Observación guardada correctamente',
            'observaciones' => $detalle->observaciones,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
