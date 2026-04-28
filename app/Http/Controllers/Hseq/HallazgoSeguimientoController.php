<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreHallazgoSeguimientoRequest;
use App\Services\Hseq\HallazgoSeguimientoService;
use Illuminate\Http\Request;

class HallazgoSeguimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $hallazgoSeguimientoService;

    public function __construct(HallazgoSeguimientoService $hallazgoSeguimientoService)
    {
        $this->hallazgoSeguimientoService = $hallazgoSeguimientoService;
    }
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHallazgoSeguimientoRequest $request)
     {
        $data = $request->validated();
        $seguimiento = $this->hallazgoSeguimientoService->crearSeguimiento($data['hallazgo_id'], $data['observacion']);
        return response()->json([
            'message' => 'Seguimiento creado exitosamente',
            'data' => $seguimiento
        ], 201);
    }
 

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $response = $this->hallazgoSeguimientoService->listarSeguimientos($id);
        return response()->json($response);
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
