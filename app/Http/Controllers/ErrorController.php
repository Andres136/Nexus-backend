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

    $erroresPorProceso = Errores::select('departamento_id', DB::raw('count(*) as total'))
        ->groupBy('departamento_id')
        ->get()
        ->map(function ($error) {
            $proceso = Departamentos::find($error->departamento_id);
            $error->proceso_nombre = $proceso ? $proceso->nombre : "Desconocido";
            return $error;
        });

    return response()->json([
        'totalErrores' => $totalErrores,
        'erroresPorProceso' => $erroresPorProceso
    ]);
}
 
}
