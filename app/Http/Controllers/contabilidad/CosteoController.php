<?php

namespace App\Http\Controllers\contabilidad;

use App\Exports\CosteoUtilidadExport;
use App\Http\Controllers\Controller;
use App\Services\contabilidad\CostoeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel;

class CosteoController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    protected $costeoService;

    public function __construct(CostoeService $costeoService)
    {
        $this->costeoService = $costeoService;
    }
public function index(Request $request)
{
    $productoId = $request->input('producto_id');
    $search = $request->input('search');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin = $request->input('fecha_fin');

    $data = $this->costeoService->utilidad(
        $productoId,
        $search,
        $fechaInicio,
        $fechaFin
    );

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
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


public function export(Request $request)
{
    $productoId = $request->input('producto_id');
    $search = $request->input('search');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin = $request->input('fecha_fin');

    $data = $this->costeoService->utilidad(
        $productoId,
        $search,
        $fechaInicio,
        $fechaFin
    );

  return Excel::download(
    new CosteoUtilidadExport(
        collect($data['detalle'])->map(function ($item) {
            return [
                'Producto' => $item->name,
                'Descripción' => $item->description,
                'KG Vendidos' => $item->total_kg_vendidos,
                'Ingreso' => $item->ingreso,
                'Costo Promedio' => $item->costo_promedio,
                'Costo Total' => $item->costo,
                'Utilidad' => $item->utilidad,
                'Margen %' => $item->margen_porcentaje,
            ];
        })->toArray()
    ),
    'costeo_utilidad.xlsx'
);
}
}
