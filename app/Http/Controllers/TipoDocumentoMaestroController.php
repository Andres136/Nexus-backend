<?php

namespace App\Http\Controllers;

use App\Models\TipoDocumentoMaestro;
use Illuminate\Http\Request;

class TipoDocumentoMaestroController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => TipoDocumentoMaestro::query()->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'nombre' => mb_strtoupper(trim((string) $request->input('nombre'))),
        ]);

        $data = $request->validate([
            'nombre' => 'required|string|max:100|unique:tipos_documento_maestro,nombre',
        ], [
            'nombre.required' => 'El nombre del tipo de documento es obligatorio.',
            'nombre.unique' => 'Este tipo de documento ya está registrado.',
        ]);

        $tipo = TipoDocumentoMaestro::create($data);

        return response()->json([
            'message' => 'Tipo de documento registrado correctamente',
            'data' => $tipo,
        ], 201);
    }
}
