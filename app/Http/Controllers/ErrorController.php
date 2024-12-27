<?php

namespace App\Http\Controllers;

use App\Http\Requests\ErrorRequest;
use App\Models\Errores;
use Illuminate\Http\Request;

class ErrorController extends Controller
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
    public function store(ErrorRequest $request)
    {
        Errores::create([
            'descripcion' => $request->descripcion,
            'proceso_id' => $request->proceso_id,
        ]);
        return response()->json([
            'message' => 'Error registrado correctamente'
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
