<?php

namespace App\Http\Controllers;

use App\Models\Tareas;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Tareas::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'fecha_fin' => $request->fecha_fin,
            'estado_id' => 1,
            'proceso_id' => $request->proceso_id,
            'user_id' => $request->user_id
        ]);
        return response()->json([
            'message' => 'Tarea registrada correctamente'
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
