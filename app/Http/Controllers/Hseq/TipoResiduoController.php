<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreTipoResiduoRequest;
use App\Http\Requests\Hseq\UpdateTipoResiduoRequest;
use App\Services\Hseq\TipoResiduoService;
use Illuminate\Http\Request;

class TipoResiduoController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    protected $tipoResiduoService;

    public function __construct(TipoResiduoService $tipoResiduoService)
    {
        $this->tipoResiduoService = $tipoResiduoService;
    }
    public function index()
    {
        return response()->json($this->tipoResiduoService->all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTipoResiduoRequest $request)
    {
        $data = $request->validated();
        $tipoResiduo = $this->tipoResiduoService->create($data);
        return response()->json([
            'message' => 'Tipo de residuo creado exitosamente',
            'data' => $tipoResiduo
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tipoResiduo = $this->tipoResiduoService->find($id);
        return response()->json($tipoResiduo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTipoResiduoRequest $request, string $id)
    {
        $data = $request->validated();
        $tipoResiduo = $this->tipoResiduoService->update($id, $data);
        return response()->json([
            'message' => 'Tipo de residuo actualizado exitosamente',
            'data' => $tipoResiduo
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->tipoResiduoService->delete($id);
        return response()->json([
            'message' => 'Tipo de residuo eliminado exitosamente'
        ]);
    }
}
