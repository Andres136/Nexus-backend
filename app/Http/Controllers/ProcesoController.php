<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcesoRequest;
use App\Models\Procesos;
use Illuminate\Http\Request;

class ProcesoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($departamento_id)
    {
        //listar todos los procesos
        $procesos = Procesos::where('departamento_id', $departamento_id)->get();

        if($procesos->isEmpty()){
            return response()->json([
                'message' => 'No hay procesos registrados'
            ], 404);
        }
        return response()->json($procesos, 200);
      
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProcesoRequest $request)
    {
        Procesos::create([
            'nombre' => $request->nombre,
            'user_id' => $request->user_id,
            'departamento_id' => $request->departamento_id,
        
        ]);
        return response()->json([
            'message' => 'Proceso registrado correctamente'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
      
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
