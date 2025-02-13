<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartamentoRequest;
use App\Http\Requests\DepartamentoUpdateRequest;
use App\Models\Departamentos;
use App\Models\Macroprocesos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DepartamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {   
        $departamentos = Departamentos::all();
        return response()->json($departamentos);
   
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DepartamentoRequest $request)
    {

        //Subir la imagen y almacenar su ruta
        $icono = $request->file('icono')->store('iconos', 'public');
        Departamentos::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'macroprocesos_id' => $request->macroprocesos_id,
            'icono' => $icono
        ]);
        return response()->json([
            'message' => 'Departamento creado con éxito'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    // Listar Procesos con su departamentos
     
    $departamento = Departamentos::with('procesos')->find($id);
    if (!$departamento) {
        return response()->json(["Error" => "Departamento no encontrado"], 404);
    }
    return response()->json($departamento);
    }

    /**
     * Update the specified resource in storage.
     */



     public function update(Request $request, $id)
     {
        $departamento = Departamentos::find($id);
        if (!$departamento) {
            return response()->json(["Error" => "Departamento no encontrado"], 404);
        }
        $departamento->nombre = $request->nombre;
        $departamento->descripcion = $request->descripcion;
        $departamento->macroprocesos_id = $request->macroprocesos_id;
        // Si se envía un icono, se actualiza
        if ($request->hasFile('icono')) {
            // Eliminar el icono anterior
            Storage::disk('public')->delete($departamento->icono);
            // Subir el nuevo icono
            $icono = $request->file('icono')->store('iconos', 'public');
            $departamento->icono = $icono;
        }
        $departamento->save();
        return response()->json([
            'message' => 'Departamento actualizado con éxito',
            'departamento' => $departamento
        ]);
     }
     
     
     
     
    /**}
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $departamento = Departamentos::find($id);
        if (!$departamento) {
            return response()->json(["Error" => "Departamento no encontrado"], 404);
        }
        $departamento->delete();
        return response()->json([
            'message' => 'Departamento eliminado con éxito'
        ], 200);
    }




}
