<?php

namespace App\Http\Controllers;

use App\Http\Requests\ErrorRequest;
use App\Models\Departamentos;
use App\Models\Errores;
use App\Models\Procesos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ErrorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       // obtener errores
       $errores = Errores::all();
         return response()->json($errores);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(ErrorRequest $request)
    {
        Errores::create([
            'descripcion' => $request->descripcion,
            'departamento_id' => $request->departamento_id,
        ]);
        return response()->json([
            'message' => 'Error registrado correctamente'
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        
    }
    // Obtener últimos 10 errores
   

    // KPI: Total de errores y agrupados por proceso
    
    
    public function kpiErrores()
    {
        $totalErrores = Errores::count();
    
        // Fechas para el mes actual y el mes anterior
        $inicioMesActual = Carbon::now()->startOfMonth();
        $finMesActual = Carbon::now()->endOfMonth();
        $inicioMesAnterior = Carbon::now()->subMonth()->startOfMonth();
        $finMesAnterior = Carbon::now()->subMonth()->endOfMonth();
    
        // Obtener total de errores del mes actual y anterior
        $totalErroresMesActual = Errores::whereBetween('created_at', [$inicioMesActual, $finMesActual])->count();
        $totalErroresMesAnterior = Errores::whereBetween('created_at', [$inicioMesAnterior, $finMesAnterior])->count();
    
        // Calcular el porcentaje de variación
        $variacion = ($totalErroresMesAnterior > 0)
            ? (($totalErroresMesActual - $totalErroresMesAnterior) / $totalErroresMesAnterior) * 100
            : 0;
    
        // Obtener errores agrupados por departamento en ambos períodos
        $erroresPorProceso = Errores::select('departamento_id', DB::raw('count(*) as total'))
            ->groupBy('departamento_id')
            ->with('departamento')
            ->get()
            ->map(function ($error) {
                return [
                    'departamento_id' => $error->departamento_id,
                    'departamento_nombre' => $error->departamento ? $error->departamento->nombre : "Desconocido",
                    'total' => $error->total
                ];
            });
    
        // Errores por proceso del mes actual
        $erroresPorProcesoMesActual = Errores::select('departamento_id', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$inicioMesActual, $finMesActual])
            ->groupBy('departamento_id')
            ->with('departamento')
            ->get()
            ->map(function ($error) {
                return [
                    'departamento_id' => $error->departamento_id,
                    'departamento_nombre' => $error->departamento ? $error->departamento->nombre : "Desconocido",
                    'total' => $error->total
                ];
            });
    
        // Errores por proceso del mes anterior
        $erroresPorProcesoMesAnterior = Errores::select('departamento_id', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$inicioMesAnterior, $finMesAnterior])
            ->groupBy('departamento_id')
            ->with('departamento')
            ->get()
            ->map(function ($error) {
                return [
                    'departamento_id' => $error->departamento_id,
                    'departamento_nombre' => $error->departamento ? $error->departamento->nombre : "Desconocido",
                    'total' => $error->total
                ];
            });
    
        return response()->json([
            'totalErrores' => $totalErrores,
            'totalErroresMesActual' => $totalErroresMesActual,
            'totalErroresMesAnterior' => $totalErroresMesAnterior,
            'variacionPorcentaje' => round($variacion, 2), // Redondeamos el porcentaje
            'erroresPorProceso' => $erroresPorProceso,
            'erroresPorProcesoMesActual' => $erroresPorProcesoMesActual,
            'erroresPorProcesoMesAnterior' => $erroresPorProcesoMesAnterior
        ]);
    }
    
 
}
