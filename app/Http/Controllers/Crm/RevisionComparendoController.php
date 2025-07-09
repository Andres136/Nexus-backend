<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreRevisionComparendoRequest;
use App\Models\Crm\RevisionComparendo;
use Illuminate\Http\Request;

class RevisionComparendoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $revisionComparendos = RevisionComparendo::with('conductor.user')->get();
        return response()->json($revisionComparendos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRevisionComparendoRequest $request)
    {
        $rutaArchivo = $request->file('archivo_soporte')->store('revision_comparendos', 'public');
        RevisionComparendo::create([
            'conductor_id' => $request->conductor_id,
            'fecha_revision' => $request->fecha_revision,
            'archivo_soporte' => $rutaArchivo,
            'observaciones' => $request->observaciones,
        ]);
        return response()->json(['message' => 'Revisión de comparendo creada exitosamente.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $revisionComparendo = RevisionComparendo::with('conductor.user')->findOrFail($id);
        return response()->json($revisionComparendo);
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
    public function porConductor($conductorId)
{
    $revisiones = RevisionComparendo::with('conductor.user')
        ->where('conductor_id', $conductorId)
        ->orderByDesc('fecha_revision')
        ->paginate(10);

    return response()->json($revisiones);
}
} 