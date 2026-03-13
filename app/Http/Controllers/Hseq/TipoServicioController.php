<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreTipoServicioRequest;
use App\Services\Hseq\TipoServicioService;
use Illuminate\Http\Request;

class TipoServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $tipoServicioService;
    public function __construct(TipoServicioService $tipoServicioService)
    {
        $this->tipoServicioService = $tipoServicioService;
    }
    public function index(Request $request)
    {
        $search = $request->query('search');
        $limit = $request->query('limit', 10);
        $tipoServicios = $this->tipoServicioService->all($search, $limit);
        return response()->json([
            'data' => $tipoServicios
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTipoServicioRequest $request)
    {
        $tipoServicio = $this->tipoServicioService->create($request->all());
        return response()->json([
            'message' => 'Tipo de servicio creado exitosamente',
            'data' => $tipoServicio
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tipoServicio = $this->tipoServicioService->find($id);
        return response()->json([
            'data' => $tipoServicio
        ], 200);
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
