<?php

namespace App\Http\Controllers\RegistroDiaro;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroDiario\StorePreguntaRequest;
use App\Services\RegistroDiario\PreguntaService;
use Illuminate\Http\Request;

class PreguntaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     protected  $preguntaService;

     public function __construct(PreguntaService $preguntaService)
     {
         $this->preguntaService = $preguntaService;
     }
   
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePreguntaRequest $request)
    {
        $this->preguntaService->crearPregunta($request->all());

            return response()->json([
                'message' => 'Pregunta creada exitosamente',
            ], 201);
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
