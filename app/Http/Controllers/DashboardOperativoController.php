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
        $filters = $request->only(['sede_id', 'proveedor_id', 'solo_pendientes', 'search', 'fecha_inicio', 'fecha_fin', 'page', 'per_page']);
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

    /**
     * DELETE /vsm/origenes/{id} — elimina el vínculo de trazabilidad de
     * compra (Trazabilidad de Prioridades). Bloqueado si ya tiene kg
     * recibidos aplicados: borrarlo dejaría esa cantidad recibida sin
     * origen de compra registrado en el resto del sistema (VSM, dashboard).
     */
    public function destroyOrigen($id)
    {
        $origen = OrdenCompraProveedorDetalleOrigen::findOrFail($id);

        if ($origen->cantidad_recibida_aplicada > 0) {
            return response()->json([
                'message' => 'No se puede eliminar: ya tiene cantidad recibida aplicada.',
            ], 422);
        }

        $origen->delete();

        return response()->json(['message' => 'Registro eliminado correctamente']);
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
        // Con muchas órdenes/detalles, dompdf agota el memory_limit (128M)
        // o el max_execution_time (30s) por defecto y la petición muere con
        // 500 antes de generar el PDF. Se amplían solo para esta acción.
        ini_set('memory_limit', '512M');
        set_time_limit(120);

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
        $detalle->update([
            'observaciones_calidad' => $request->observaciones,
            'observaciones_calidad_usuario_id' => $request->user()->id,
            'observaciones_calidad_at' => now(),
        ]);

        return response()->json([
            'message' => 'Observación guardada correctamente',
            'observaciones' => $detalle->observaciones_calidad,
            'observaciones_usuario' => $request->user()->name,
            'observaciones_at' => $detalle->observaciones_calidad_at,
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
