<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreInspecionRequest;
use App\Services\Hseq\InspeccionHseqService;
use Illuminate\Http\Request;

class InspeccionHseqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $inspeccionService;

    public function __construct(InspeccionHseqService $inspeccionService)
    {
        $this->inspeccionService = $inspeccionService;
    }
    public function index()
    {
        $inspecciones = $this->inspeccionService->all();
        return response()->json([
            'data' => $inspecciones
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInspecionRequest $request)
    {
        $data = $request->validated();
        $inspeccion = $this->inspeccionService->create($data);
        return response()->json([
            'message' => 'Inspección creada exitosamente',
            'data' => $inspeccion
        ]);
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
        $data = $request->all();
        $inspeccion = $this->inspeccionService->update($id, $data);
        return response()->json([
            'message' => 'Inspección actualizada exitosamente',
            'data' => $inspeccion
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = $this->inspeccionService->delete($id);
        return response()->json([
            'message' => 'Inspección eliminada exitosamente',
            'data' => $data
        ]);
    }
}
