<?php

namespace App\Http\Controllers;

use App\Services\Crm\DhasboardOperativoService;
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
     public function getDatosDashboard()
    {
        $datos = $this->service->getDatosDashboard();
        return response()->json($datos);
    }   

public function index(Request $request)
{
    $filters = $request->all();

    $sedeId = $request->get('sede_id') 
        ?? auth()->user()->sede_id;

    $perPage = $request->get('per_page', 10);

    return response()->json(
        $this->service->obtenerTrazabilidadGeneral($filters, $sedeId, $perPage)
    );
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
    public function show($productoId, Request $request)
    {
        $filters = $request->all();
        $data = $this->service->obtenerTrazabilidad($productoId, $filters);

        return response()->json($data);
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
