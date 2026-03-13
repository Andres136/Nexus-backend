<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreHallazgoNovedadRequest;
use App\Services\Hseq\HallazgoNovedadService;
use Illuminate\Http\Request;

class HallazgoNovedadController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $hallazgoNovedadService;

    public function __construct(HallazgoNovedadService $hallazgoNovedadService)
    {
        $this->hallazgoNovedadService = $hallazgoNovedadService;
    }
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHallazgoNovedadRequest $request)
    {
        $data = $request->validated();
        $hallazgoNovedad = $this->hallazgoNovedadService->create($data);
        return response()->json([
            'message' => 'Hallazgo Novedad creado exitosamente',
            'data' => $hallazgoNovedad
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $hallazgoNovedad = $this->hallazgoNovedadService->find($id);
        return response()->json([
            'message' => 'Hallazgo Novedad encontrado',
            'data' => $hallazgoNovedad
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->all();
        $hallazgoNovedad = $this->hallazgoNovedadService->update($id, $data);
        return response()->json([
            'message' => 'Hallazgo Novedad actualizado exitosamente',
            'data' => $hallazgoNovedad
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
