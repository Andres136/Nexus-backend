<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentoMaestroRequest;
use App\Services\DocumentoMaestroService;
use Illuminate\Http\Request;

class DocumentoMaestroController extends Controller
{
    protected DocumentoMaestroService $documentoMaestroService;

    public function __construct(DocumentoMaestroService $documentoMaestroService)
    {
        $this->documentoMaestroService = $documentoMaestroService;
    }

    public function index(Request $request)
    {
        $request->validate([
            'departamento_id' => 'required|integer|exists:departamentos,id',
        ]);

        $documentos = $this->documentoMaestroService->listarPorDepartamento(
            (int) $request->query('departamento_id')
        );

        return response()->json(['data' => $documentos]);
    }

    public function show(string $id)
    {
        $documento = $this->documentoMaestroService->obtener((int) $id);

        return response()->json(['data' => $documento]);
    }

    public function store(DocumentoMaestroRequest $request)
    {
        $documento = $this->documentoMaestroService->crear($request->validated());

        return response()->json([
            'message' => 'Documento maestro registrado correctamente',
            'data' => $documento,
        ], 201);
    }

    public function update(DocumentoMaestroRequest $request, string $id)
    {
        $documento = $this->documentoMaestroService->actualizar((int) $id, $request->validated());

        return response()->json([
            'message' => 'Documento maestro actualizado correctamente',
            'data' => $documento,
        ]);
    }

    public function destroy(string $id)
    {
        $this->documentoMaestroService->eliminar((int) $id);

        return response()->json([
            'message' => 'Documento maestro eliminado correctamente',
        ]);
    }
}
