<?php

namespace App\Http\Controllers;

use App\Http\Requests\TareaRequest;
use App\Models\Tareas;
use App\Models\User;
use App\Notifications\NuevaTareaAsignada;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $tareas = Tareas::with('usuario','departamentos')->paginate(10);
        return response()->json($tareas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TareaRequest $request)
    {
       $tarea= Tareas::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'fecha_fin' => $request->fecha_fin,
            'estado_id' => 1,
            'departamento_id' => $request->departamento_id,
            'user_id' => $request->user_id
        ]);

        $usuario =User::find($request->user_id);

        $usuario->notify(new NuevaTareaAsignada($tarea));



        return response()->json([
            'message' => 'Tarea registrada correctamente y notificación enviada'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tarea = Tareas::with('usuario','departamentos')->find($id);
        return response()->json($tarea);


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
        
        $tarea = Tareas::find($id);
        $tarea->delete();
        return response()->json([
            'message' => 'Tarea Completada'
        ]);
    }
}
