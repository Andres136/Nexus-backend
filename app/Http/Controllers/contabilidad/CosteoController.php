<?php

namespace App\Http\Controllers\contabilidad;

use App\Exports\CosteoUtilidadExport;
use App\Http\Controllers\Controller;
use App\Models\Crm\empresa;
use App\Services\contabilidad\CostoeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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
    $empresaId = $request->input('empresa_id');
    $search = $request->input('search');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin = $request->input('fecha_fin');

    $data = $this->costeoService->utilidad(
        $productoId,
        $empresaId,
        $search,
        $fechaInicio,
        $fechaFin
    );

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}
public function export(Request $request)
{
    $productoId = $request->input('producto_id');
    $empresaId = $request->input('empresa_id');
    $search = $request->input('search');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin = $request->input('fecha_fin');

    $data = $this->costeoService->utilidad(
        $productoId,
        $empresaId,
        $search,
        $fechaInicio,
        $fechaFin,
        50,
        false
    );

    $empresa = $empresaId ? empresa::find($empresaId) : null;

    $logoPath = null;

    if ($empresa && $empresa->logo) {
        $possiblePath = public_path('storage/' . $empresa->logo);

        if (file_exists($possiblePath) && !is_dir($possiblePath)) {
            $logoPath = $possiblePath;
        }
    }

    $nombreArchivo = 'costeo_utilidad';

    if ($empresa) {
        $nombreArchivo .= '_' . Str::slug($empresa->nombre, '_');
    }

    $nombreArchivo .= '_' . now()->format('Y-m-d') . '.xlsx';

  return Excel::download(
    new CosteoUtilidadExport(
        $data['detalle']->map(function ($item) {
            return [
                'Producto' => $item->name,
                'Descripción' => $item->description,
                'KG Vendidos' => $item->total_kg_vendidos,
                'Ingreso sin IVA' => $item->ingreso,
                'Costo Promedio KG sin IVA' => $item->costo_promedio,
                'Costo Total sin IVA' => $item->costo,
                'KG Comprados' => $item->kg_comprado,
                'Costo Total Comprado sin IVA' => $item->costo_comprado,
                'Utilidad sin IVA' => $item->utilidad,
                'Margen %' => $item->margen_porcentaje,
            ];
        })->toArray(),
        $empresa?->nombre,
        $logoPath
    ),
    $nombreArchivo
);
}
}
