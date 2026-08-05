<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\CerrarHallazgoRequest;
use App\Models\Crm\bodega;
use App\Models\Hseq\InspeccionHseq;
use App\Services\Hseq\HseqDashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HseqDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $dashboardService;
    public function __construct(HseqDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

     /**
     * Display the dashboard data.
     */ 
    public function index()
    {
        return response()->json($this->dashboardService->getDashboard());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    //Hallazgos recientes
public function hallazgos(Request $request)
{
    return $this->dashboardService->hallazgos(
        $request->all(),
        $request->search
    );
}
public function descargarHallazgosPdf(Request $request)
{
    $filters = $request->all();

    $hallazgos = $this->dashboardService->hallazgosParaPdf($filters);

    $inspeccionFiltro = null;
    $empresaLogo = null;
    if (!empty($filters['inspeccion_id'])) {
        $inspeccion = InspeccionHseq::with('empresa')->find($filters['inspeccion_id']);

        if ($inspeccion && $hallazgos->isNotEmpty()) {
            $primero = $hallazgos->first();
            $inspeccionFiltro = "{$primero->tipo_inspeccion} — {$primero->fecha}";
        }

        if ($inspeccion?->empresa?->logo && Storage::disk('public')->exists($inspeccion->empresa->logo)) {
            $empresaLogo = Storage::disk('public')->path($inspeccion->empresa->logo);
        }
    }

    $bodegaFiltro = null;
    if (!empty($filters['bodega_id'])) {
        $bodegaFiltro = bodega::find($filters['bodega_id'])?->nombre;
    }

    $pdf = Pdf::loadView('pdf.hallazgos_hseq', [
        'hallazgos' => $hallazgos,
        'filters' => $filters,
        'inspeccionFiltro' => $inspeccionFiltro,
        'bodegaFiltro' => $bodegaFiltro,
        'empresaLogo' => $empresaLogo,
        'generadoEn' => now(),
        'generadoPor' => auth()->user()?->name,
    ]);

    return $pdf->stream('hallazgos_hseq.pdf');
}

public function inspeccionesFinalizadas(Request $request)
{
    return response()->json(
        $this->dashboardService->getInspeccionesFinalizadas(
            $request->query('search'),
            $request->only(['fecha_inicio', 'fecha_fin', 'sede_id', 'bodega_id', 'responsable_id', 'estado'])
        )
    );
}

public function cerrarHallazgo(CerrarHallazgoRequest $request, string $id)
{
    try {
        $hallazgo = $this->dashboardService->cerrarHallazgo(
            $id,
            $request->file('foto'),
            $request->input('observaciones_cierre')
        );
    } catch (\RuntimeException $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }

    return response()->json([
        'message' => 'Hallazgo cerrado exitosamente',
        'data' => $hallazgo,
    ]);
}
}
