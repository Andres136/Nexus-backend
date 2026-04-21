<?php

namespace App\Http\Controllers\contabilidad;

use App\Http\Controllers\Controller;
use App\Services\contabilidad\CostoeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}
