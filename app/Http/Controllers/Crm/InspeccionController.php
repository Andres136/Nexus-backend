<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\InspeccionesRequest;
use App\Models\Crm\Inspeccion;
use Illuminate\Http\Request;

class InspeccionController extends Controller
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
    public function store(InspeccionesRequest $request)
    {
        $inspeccion = Inspeccion::create([
            'vehiculo_id' => $request->vehiculo_id,
            'fecha' => $request->fecha,
            'responsable' => $request->responsable,
            'observaciones' => $request->observaciones,
            'estado_general' => $request->estado_general,

        ]);

        $inspeccion->save();

        return response()->json([
            'message' => 'Inspección creada correctamente',
            'inspeccion' => $inspeccion,
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
        $inspeccion = Inspeccion::findOrFail($id);
        $inspeccion->update($request->all());

        return response()->json([
            'message' => 'Inspección actualizada correctamente',
            'inspeccion' => $inspeccion,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
