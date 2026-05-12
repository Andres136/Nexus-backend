<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreSoporteTareaRequest;
use App\Services\Hseq\SoporteTareaService;
use Illuminate\Http\Request;

class SoporteTareaController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $soporteTareaService;
    public function __construct(SoporteTareaService $soporteTareaService)
    {
        $this->soporteTareaService = $soporteTareaService;
    }
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSoporteTareaRequest $request)
    {
        $soportes = $this->soporteTareaService->crear($request->all());

        return response()->json([
            'message' => 'Soportes de tarea creados correctamente',
            'data' => $soportes
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //


    }

    public function getByTareaId(string $tareaId)
    {
        $soportes = $this->soporteTareaService->getByTarea((int)$tareaId);

        return response()->json([
            'message' => 'Soportes de tarea obtenidos correctamente',
            'data' => $soportes
        ], 200);
    }

    public function getByHallazgoId(string $hallazgoId)
    {
        $soportes = $this->soporteTareaService->getByHallazgo((int)$hallazgoId);

        return response()->json([
            'message' => 'Soportes de hallazgo obtenidos correctamente',
            'data' => $soportes
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
