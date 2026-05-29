<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CarpetaRequest;
use App\Models\Crm\Carpeta;
use Illuminate\Http\Request;

class CarpetaController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $parentId = $request->has('parent_id')
            ? ($request->get('parent_id') !== '' ? (int) $request->get('parent_id') : null)
            : null;

        // Búsqueda global (ignora nivel actual)
        if ($search) {
            return response()->json(
                Carpeta::withCount(['documentos', 'subcarpetas'])
                    ->where('nombre', 'LIKE', "%$search%")
                    ->orderBy('nombre')
                    ->paginate(20)
            );
        }

        // Navegar por nivel
        return response()->json(
            Carpeta::withCount(['documentos', 'subcarpetas'])
                ->where('parent_id', $parentId)
                ->orderBy('nombre')
                ->paginate(50)
        );
    }

    public function store(CarpetaRequest $request)
    {
        $carpeta = Carpeta::create([
            'nombre'    => $request->nombre,
            'parent_id' => $request->parent_id ?? null,
        ]);

        return response()->json([
            'data'    => $carpeta,
            'message' => 'Carpeta creada correctamente',
        ], 201);
    }

    public function show(string $id)
    {
        return response()->json(
            Carpeta::withCount(['documentos', 'subcarpetas'])->findOrFail($id)
        );
    }

    public function destroy(string $id)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if ($user->role_id !== 1) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $carpeta = Carpeta::with(['subcarpetas', 'documentos'])->findOrFail($id);
        $this->eliminarRecursivo($carpeta);

        return response()->json(['message' => 'Carpeta eliminada correctamente']);
    }

    public function mover(Request $request, string $id)
    {
        $carpeta      = Carpeta::findOrFail($id);
        $nuevoParentId = $request->input('parent_id'); // null = mover a raíz

        if ($nuevoParentId !== null) {
            // Prevenir mover dentro de sí misma o sus descendientes
            $actual = Carpeta::find($nuevoParentId);
            while ($actual !== null) {
                if ($actual->id === (int) $id) {
                    return response()->json([
                        'message' => 'No se puede mover una carpeta dentro de sí misma',
                    ], 422);
                }
                $actual = $actual->parent_id ? Carpeta::find($actual->parent_id) : null;
            }
        }

        $carpeta->update(['parent_id' => $nuevoParentId]);

        return response()->json(['message' => 'Carpeta movida correctamente']);
    }

    private function eliminarRecursivo(Carpeta $carpeta): void
    {
        foreach ($carpeta->subcarpetas as $sub) {
            $sub->load(['subcarpetas', 'documentos']);
            $this->eliminarRecursivo($sub);
        }
        $carpeta->documentos()->delete();
        $carpeta->delete();
    }
}
