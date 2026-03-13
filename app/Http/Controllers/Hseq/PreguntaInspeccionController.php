<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StorePreguntaInspeccionRequest;
use App\Services\Hseq\PreguntasInspeccionService;
use Illuminate\Http\Request;

class PreguntaInspeccionController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $preguntaInspeccionService;


    public function __construct(PreguntasInspeccionService $preguntaInspeccionService)
    {
        $this->preguntaInspeccionService = $preguntaInspeccionService;
    }
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 10);
        return $this->preguntaInspeccionService->all($search, $limit);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePreguntaInspeccionRequest $request)
    {
        $data = $request->validated();
        $respuestas = $this->preguntaInspeccionService->create($data);
        return response()->json([
            'message' => 'Pregunta de inspección creada exitosamente',
            'data' => $respuestas
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pregunta = $this->preguntaInspeccionService->find($id);
        return response()->json([
            'message' => 'Pregunta de inspección encontrada exitosamente',
            'data' => $pregunta
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->all();
        $pregunta = $this->preguntaInspeccionService->update($id, $data);
        return response()->json([
            'message' => 'Pregunta de inspección actualizada exitosamente',
            'data' => $pregunta
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->preguntaInspeccionService->delete($id);
        return response()->json([
            'message' => 'Pregunta de inspección eliminada exitosamente'
        ]);
    }
}
