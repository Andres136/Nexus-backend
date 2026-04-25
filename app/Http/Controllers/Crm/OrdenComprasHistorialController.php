<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreOrdenComprasHistorialRequest;
use App\Services\Crm\OrdenCompraHistorialService;
use Illuminate\Http\Request;

class OrdenComprasHistorialController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $ordenCompraHistorialService;
    public function __construct(OrdenCompraHistorialService $ordenCompraHistorialService)
    {
        $this->ordenCompraHistorialService = $ordenCompraHistorialService;
    }
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(StoreOrdenComprasHistorialRequest $request)
{
    $data = $request->validated();

    $orden = $this->ordenCompraHistorialService->actualizarFechaOrden($data);

    return response()->json([
        'message' => 'Fecha actualizada correctamente.',
        'data' => $orden
    ], 200);
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
