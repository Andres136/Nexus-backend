<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreReportesBicRequest;
use App\Services\Hseq\ReporteBicService;
use Illuminate\Http\Request;



class ReporteBicConttroller extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $service;
    public function __construct(ReporteBicService $service)
    {
        $this->service = $service;
    }
    public function index()
    {
      $reportes = $this->service->obtenerTodosLosReportes();
      return response()->json(['success' => true, 'data' => $reportes]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReportesBicRequest $request)
    {
        $reporte = $this->service->guardarReporte($request->validated());
        return response()->json(['success' => true, 'data' => $reporte]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        $reporte = $this->service->obtenerReportePorUuid($uuid);
        return response()->json(['success' => true, 'data' => $reporte]);
    }
   

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $reporte = $this->service->editarReporte($id, $request->all());
        return response()->json(['success' => true, 'data' => $reporte]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
     {
         $this->service->eliminarReporte($uuid);
         return response()->json(['success' => true, 'message' => 'Reporte eliminado correctamente.']);
     }  
  
}
