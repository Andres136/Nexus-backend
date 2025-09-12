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
    public function index(Request $request)
    {
        $query = Tareas::with('usuario', 'departamentos');
    
        // Filtrar por nombre de usuario si se envía un parámetro de búsqueda
        if ($request->has('usuario')) {
            $query->whereHas('usuario', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->usuario . '%');
            });
        }
        $query->orderByRaw('estado_id = 1 DESC');
        $tareas = $query->paginate(10);
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
        $tarea = Tareas::find($id);

        if (!$tarea) {
            return response()->json(['message' => 'Tarea no encontrada'], 404);
        }
    
        $tarea->estado_id = 2; // Asume que "2" representa "completada"
        $tarea->save();
    
        return response()->json([
            'message' => 'Tarea marcada como completada'
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
        // 1) Query base con eager‐loads (igual que en index)
        $query = Tareas::with('usuario','departamentos');
    
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('departamento_id')) {
            $query->where('departamento_id', $request->departamento_id);
        }
    
        // 2) Resumen mensual (clone para no “ensuciar” el builder principal)
        $resumen = (clone $query)
            ->selectRaw('MONTH(created_at) AS mes')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN estado_id = 1 THEN 1 ELSE 0 END) AS pendientes')
            ->selectRaw('SUM(CASE WHEN estado_id = 2 THEN 1 ELSE 0 END) AS completadas')
            ->groupByRaw('MONTH(created_at)')
            ->orderByRaw('MONTH(created_at)')
            ->get();
    
// 2) Resumen mensual (clone para no “ensuciar” el builder principal)
$resumenTareas = (clone $query)->get();

// IDs únicos de usuarios y departamentos en las tareas del resumen
$userIds = $resumenTareas->pluck('user_id')->unique()->filter()->values();
$departamentoIds = $resumenTareas->pluck('departamento_id')->unique()->filter()->values();

// 2) Resumen mensual agrupado por mes
$resumen = $resumenTareas
    ->groupBy(function ($t) { return \Carbon\Carbon::parse($t->created_at)->month; })
    ->map(function ($group, $mes) {
        return [
            'mes'         => $mes,
            'total'       => $group->count(),
            'pendientes'  => $group->where('estado_id', 1)->count(),
            'completadas' => $group->where('estado_id', 2)->count(),
        ];
    })->values();

// 3) Solo usuarios con tareas en el resumen
$usuarios = User::whereIn('id', $userIds)
    ->get(['id', 'name']);

// 4) Solo departamentos con tareas en el resumen
$departamentos = \App\Models\Departamentos::whereIn('id', $departamentoIds)
    ->get(['id', 'nombre']);

return response()->json([
    'resumen'      => $resumen,
    'usuarios'     => $usuarios,
    'departamentos'=> $departamentos,
]);
    }
    
    
    

}
