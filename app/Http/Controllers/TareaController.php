<?php

namespace App\Http\Controllers;

use App\Http\Requests\TareaRequest;
use App\Models\Tareas;
use App\Models\User;
use App\Notifications\NuevaTareaAsignada;
use App\Services\TareaVencidaService;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    /**
     * Display a listing of the resource.
     */

public function index(Request $request, TareaVencidaService $tareaVencidaService)
{
    $tareaVencidaService->notificarTareasVencidas(); // Ejecuta la lógica de notificación
    $user = auth()->user();

    $query = Tareas::with('usuario', 'departamentos');

    // 🔹 Solo pendientes (1) y en curso (5)
    $query->whereIn('estado_id', [1, 5]);

    // 🔹 Si NO es admin (1) ni supervisor (20), solo ve sus tareas
    if (!in_array($user->role_id, [1, 20])) {
        $query->where('user_id', $user->id);
    }

    // 🔹 Filtro por nombre de usuario
    if ($request->filled('usuario')) {
        $query->whereHas('usuario', function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->usuario . '%');
        });
    }

    // 🔹 Filtro por departamento
    if ($request->filled('departamento')) {
        $query->whereHas('departamentos', function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->departamento . '%');
        });
    }

    // 🔹 Ordenar por fecha
    $query->orderBy('created_at', 'desc');

    return response()->json($query->get()); // ❌ sin paginación
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
            'user_id' => $request->user_id,
            'user_id_creo' => auth()->id()
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
    $tarea = Tareas::find($id);

    if (!$tarea) {
        return response()->json([
            'message' => 'Tarea no encontrada'
        ], 404);
    }

    $estadoActual = $tarea->estado_id;

    // Flujo de estados
    if ($estadoActual == 1) {
        $tarea->estado_id = 5; // En curso (Entrega parcial)
    } 
    elseif ($estadoActual == 5) {
        $tarea->estado_id = 2; // Completada
    }
  $tarea->fecha_cerrado = now();
    $tarea->save();

    return response()->json([
        'message' => 'Estado actualizado correctamente',
        'estado_actual' => $tarea->estado_id
    ]);
}


    //Actualizar tarea
    public function actualizarTarea(Request $request, string $id)
    {
        $tarea = Tareas::find($id);

        if (!$tarea) {
            return response()->json(['message' => 'Tarea no encontrada'], 404);
        }
    
        $tarea->nombre = $request->nombre ?? $tarea->nombre;
        $tarea->descripcion = $request->descripcion ?? $tarea->descripcion;
        $tarea->fecha_fin = $request->fecha_fin ?? $tarea->fecha_fin;
        $tarea->departamento_id = $request->departamento_id ?? $tarea->departamento_id;
        $tarea->user_id = $request->user_id ?? $tarea->user_id;
        $tarea->save();
    
        return response()->json([
            'message' => 'Tarea actualizada correctamente'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
        
    //     $tarea = Tareas::find($id);
    //     $tarea->delete();
    //     return response()->json([
    //         'message' => 'Tarea Completada'
    //     ]);
    // }
    public function lineaTiempo()
    {
        $tareas = Tareas::with('usuario') // si necesitas el nombre del usuario
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($tarea) {
                return [
                    'id' => $tarea->id,
                    'nombre' => $tarea->nombre,
                    'estado' => $tarea->estado_id,
                    'usuario' => $tarea->usuario->name ?? 'N/A',
                    'inicio' => $tarea->created_at->format('Y-m-d'),
                    'fin' => $tarea->fecha_fin,
                    'vencida' => now()->gt($tarea->fecha_fin),
                ];
            });
    
        return response()->json($tareas);
    }


public function resumenMensualFiltrado(Request $request)
{
    $query = Tareas::with('usuario','departamentos');

    if ($request->filled('user_id')) {
        $query->where('user_id', $request->user_id);
    }
    if ($request->filled('departamento_id')) {
        $query->where('departamento_id', $request->departamento_id);
    }

    // Trae todas las tareas filtradas
    $tareasFiltradas = (clone $query)->get();

    // Agrupa por mes y agrega los IDs de usuario y departamento
    $resumen = $tareasFiltradas
        ->groupBy(function ($t) { return \Carbon\Carbon::parse($t->created_at)->month; })
        ->map(function ($group, $mes) {
            return [
                'mes'         => $mes,
                'total'       => $group->count(),
                'pendientes'  => $group->where('estado_id', 1)->count(),
                'completadas' => $group->where('estado_id', 2)->count(),
                'user_ids'    => $group->pluck('user_id')->unique()->values(),
                'departamento_ids' => $group->pluck('departamento_id')->unique()->values(),
            ];
        })->values();

    // Usuarios únicos de las tareas filtradas
    $usuarios = $tareasFiltradas
        ->pluck('usuario')
        ->filter()
        ->unique('id')
        ->values()
        ->map(fn($u) => [
            'id'   => $u->id,
            'name' => $u->name,
        ]);

    // Departamentos únicos de las tareas filtradas
    $departamentos = $tareasFiltradas
        ->pluck('departamentos')
        ->filter()
        ->unique('id')
        ->values()
        ->map(fn($d) => [
            'id'     => $d->id,
            'nombre' => $d->nombre,
        ]);

    return response()->json([
        'resumen'      => $resumen,
        'usuarios'     => $usuarios,
        'departamentos'=> $departamentos,
    ]);
}
    
    
    

}
