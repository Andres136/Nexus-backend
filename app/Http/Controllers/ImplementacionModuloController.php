<?php

namespace App\Http\Controllers;

use App\Models\ImplementacionModulo;
use Illuminate\Http\Request;

class ImplementacionModuloController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        return response()->json(
            ImplementacionModulo::orderByDesc('fecha_despliegue')->paginate($perPage)
        );
    }

    /**
     * Implementaciones activas, para mostrar en la ventanita del navbar.
     */
    public function vigentes()
    {
        return response()->json(
            ImplementacionModulo::where('activo', true)
                ->orderBy('fecha_despliegue')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'fecha_despliegue' => 'required|date',
            'activo' => 'sometimes|boolean',
        ]);

        $implementacion = ImplementacionModulo::create($data);

        return response()->json($implementacion, 201);
    }

    public function update(Request $request, string $id)
    {
        $implementacion = ImplementacionModulo::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'fecha_despliegue' => 'required|date',
            'activo' => 'sometimes|boolean',
        ]);

        $implementacion->update($data);

        return response()->json($implementacion);
    }

    public function destroy(string $id)
    {
        ImplementacionModulo::findOrFail($id)->delete();

        return response()->json(['message' => 'Implementación eliminada']);
    }
}
