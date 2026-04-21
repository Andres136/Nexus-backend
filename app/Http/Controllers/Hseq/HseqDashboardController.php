<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Services\Hseq\HseqDashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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

    $pdf = Pdf::loadView('pdf.hallazgos_hseq', [
        'hallazgos' => $hallazgos,
        'filters' => $filters
    ]);

    return $pdf->stream('hallazgos_hseq.pdf');
}

public function inspeccionesFinalizadas()
{
    return response()->json(
        $this->dashboardService->getInspeccionesFinalizadas()
    );
}
}
