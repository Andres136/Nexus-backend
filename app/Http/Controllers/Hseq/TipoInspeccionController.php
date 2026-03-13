<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreTipoInspeccionRequest;
use App\Services\Hseq\TipoInspeccionService;
use Illuminate\Http\Request;

class TipoInspeccionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $tipoInspeccionService;

    public function __construct(TipoInspeccionService $tipoInspeccionService)
    {
        $this->tipoInspeccionService = $tipoInspeccionService;
    }
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 10);
        $tiposInspecciones = $this->tipoInspeccionService->all($search, $limit);
        return response()->json($tiposInspecciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTipoInspeccionRequest $request)
    {
        $data = $request->validated();
        $tipoInspeccion = $this->tipoInspeccionService->create($data);
        return response()->json([
            'message' => 'Tipo de inspección creado exitosamente',
            'data' => $tipoInspeccion
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tipoInspeccion = $this->tipoInspeccionService->find($id);
        return response()->json($tipoInspeccion);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->all();
        $tipoInspeccion = $this->tipoInspeccionService->find($id);
        $updatedTipoInspeccion = $this->tipoInspeccionService->update($tipoInspeccion, $data);
        return response()->json([
            'message' => 'Tipo de inspección actualizado exitosamente',
            'data' => $updatedTipoInspeccion
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tipoInspeccion = $this->tipoInspeccionService->find($id);
        $this->tipoInspeccionService->delete($tipoInspeccion);
        return response()->json([
            'message' => 'Tipo de inspección eliminado exitosamente'
        ]);
    }
}
