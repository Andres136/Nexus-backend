<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreBodegaRequest;
use App\Models\Crm\bodega;
use Illuminate\Http\Request;

class BodegaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bodegas = bodega::all();
        return response()->json($bodegas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBodegaRequest $request)
    {
      try {
        $data = $request->validated();

        // Asignar estado_id por defecto si no viene en el request
        $data['estado_id'] = $data['estado_id'] ?? 3;

        $bodega = Bodega::create($data);

        return response()->json([
            'message' => 'Bodega creada exitosamente',
            'data' => $bodega
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al crear la bodega',
            'error' => $e->getMessage()
        ], 500);
    }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $bodega = bodega::findOrFail($id);
            return response()->json($bodega);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Bodega no encontrada', 'error' => $e->getMessage()], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $query = bodega::where('id', $id);
        if ($query->exists()) {
            $bodega = $query->first();
            $bodega->update($request->all());
            return response()->json(['message' => 'Bodega actualizada exitosamente', 'data' => $bodega], 200);
        } else {
            return response()->json(['message' => 'Bodega no encontrada'], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //si 3 es activo y 4 inactivo
        $query = bodega::where('id', $id);
        if ($query->exists()) {
            $bodega = $query->first();
            $bodega->estado_id = 4;
            $bodega->save();
            return response()->json(['message' => 'Bodega desactivada exitosamente', 'data' => $bodega], 200);
        } else {
            return response()->json(['message' => 'Bodega no encontrada'], 404);
        }
    }
}
