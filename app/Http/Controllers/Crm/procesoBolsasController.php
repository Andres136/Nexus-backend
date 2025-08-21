<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\procesoBolsasRequest;
use App\Models\Crm\proceso_bolsas;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;

class procesoBolsasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $proceso_bolsas = proceso_bolsas::all();
        return response()->json($proceso_bolsas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(procesoBolsasRequest $request)
    {
        $proceso_bolsas = proceso_bolsas::create([
            'nombre' => $request->nombre,
        ]);
        return response()->json(['message' => 'Proceso de bolsa creado con éxito', 'data' => $proceso_bolsas], 201);
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
