<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreRespuesteInspeccionRequest;
use App\Services\Hseq\RespuestaInspeccionService;
use Illuminate\Http\Request;

class RespuestaInspeccionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $respuestaService;
    public function __construct(RespuestaInspeccionService $respuestaService)
    {
        $this->respuestaService = $respuestaService;
    }
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRespuesteInspeccionRequest $request)
    {
        $data = $request->validated();
        $respuesta = $this->respuestaService->create($data);
        return response()->json([
            'message' => 'Respuesta de inspección creada exitosamente',
            'data' => $respuesta
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
