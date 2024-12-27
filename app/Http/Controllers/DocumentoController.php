<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentoRequest;
use App\Models\Documentos;
use Illuminate\Http\Request;

class DocumentoController extends Controller
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
    public function store(DocumentoRequest $request)
    {    

        $path = $request->file('documento')->store('documentos');
        Documentos::create([
            'nombre' => $request->nombre,
            'documento' => $path, 
            'proceso_id' => $request->proceso_id,
            'user_id' => $request->user_id,
            'version' => $request->version
        ]);
        return response()->json([
            'message' => 'Documento registrado correctamente'
        ], 201); 
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
       //listar todos los documentos para un usuario y a que proceso pertenecen
        $documentos = Documentos::with('usuarios','procesos')->find($id);
        if (!$documentos) {
            return response()->json(["Error" => "Documento no encontrado"], 404);
        }
        return response()->json($documentos);
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
