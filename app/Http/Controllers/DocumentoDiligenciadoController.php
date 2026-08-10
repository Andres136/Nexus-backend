<?php

namespace App\Http\Controllers;

use App\Models\DocumentoDiligenciado;
use App\Models\Documentos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentoDiligenciadoController extends Controller
{
    public function index(string $documentoId)
    {
        $diligenciados = DocumentoDiligenciado::with('usuario')
            ->where('documento_id', $documentoId)
            ->latest()
            ->get();

        return response()->json(['data' => $diligenciados]);
    }

    public function store(Request $request, string $documentoId)
    {
        Documentos::findOrFail($documentoId);

        $request->validate([
            // Límite alineado con upload_max_filesize/post_max_size del php.ini (100M)
            'archivo' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:102400',
            'observaciones' => 'nullable|string',
        ], [
            'archivo.required' => 'Selecciona un archivo.',
            'archivo.mimes' => 'El archivo debe ser PDF, DOC, DOCX, XLS o XLSX.',
            'archivo.max' => 'El archivo no debe exceder los 100MB.',
        ]);

        $nombreOriginal = $request->file('archivo')->getClientOriginalName();
        $ruta = $request->file('archivo')->storeAs('documentos-diligenciados', time() . $nombreOriginal, 'public');

        $diligenciado = DocumentoDiligenciado::create([
            'documento_id' => $documentoId,
            'user_id' => auth()->id(),
            'archivo' => $ruta,
            'nombre_original' => $nombreOriginal,
            'observaciones' => $request->observaciones,
        ]);

        return response()->json([
            'message' => 'Diligenciado guardado correctamente',
            'data' => $diligenciado->load('usuario'),
        ], 201);
    }

    public function descargar(string $id)
    {
        $diligenciado = DocumentoDiligenciado::findOrFail($id);
        $filepath = storage_path('app/public/' . $diligenciado->archivo);

        if (!file_exists($filepath)) {
            return response()->json(['message' => 'Archivo no encontrado'], 404);
        }

        return response()->download($filepath, $diligenciado->nombre_original ?: basename($diligenciado->archivo));
    }

    public function destroy(string $id)
    {
        $diligenciado = DocumentoDiligenciado::findOrFail($id);

        Storage::disk('public')->delete($diligenciado->archivo);
        $diligenciado->delete();

        return response()->json(['message' => 'Diligenciado eliminado correctamente']);
    }
}
