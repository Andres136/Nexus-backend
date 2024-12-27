<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartamentoRequest;
use App\Models\Departamentos;
use App\Models\Macroprocesos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class DepartamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
   
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DepartamentoRequest $request)
    {
        Departamentos::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'macroprocesos_id' => $request->macroprocesos_id
        ]);
        return response()->json([
            'message' => 'Departamento creado con éxito'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    // Listar Procesos con su departamentos
     
    $departamento = Departamentos::with('procesos')->find($id);
    if (!$departamento) {
        return response()->json(["Error" => "Departamento no encontrado"], 404);
    }
    return response()->json($departamento);
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
