<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\MantenimientoRequest;
use App\Http\Requests\Crm\MantenimientoUpdateRequest;
use App\Models\Crm\Mantenimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MantenimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $mantenimientos = Mantenimiento::with('vehiculo')
            ->orderBy('fecha_programada')
            ->get();

        return response()->json($mantenimientos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MantenimientoRequest $request)
    {
        $nombreArchivo = null;

        if ($request->hasFile('archivo')) {
            $uniqueName = time() . $request->file('archivo')->getClientOriginalName();
            $nombreArchivo = $request->file('archivo')->storeAs('mantenimientos', $uniqueName, 'public');
        }

            $mantenimiento = Mantenimiento::create([
                'vehiculo_id'=> $request->vehiculo_id,
                'fecha_programada'=> $request->fecha_programada,
                'fecha_realizado'=> $request->fecha_realizado,
                'taller'=> $request->taller,
                'descripcion_trabajo'=> $request->descripcion_trabajo,
                'costo'=> $request->costo,
                'kilometro_programado'=> $request->kilometro_programado,
                'tipo_mantenimiento'=> $request->tipo_mantenimiento,
                'archivo' => $nombreArchivo, // Guardar el nombre del archivo en la base de datos
                'kilometraje_actual' => $request->kilometraje_actual, // Guardar el kilometraje actual
            ]);

            return response()->json([
                'message' => 'Mantenimiento creado exitosamente',
                'mantenimiento' => $mantenimiento
            ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $mantenimiento = Mantenimiento::with('vehiculo')->findOrFail($id);

        return response()->json($mantenimiento);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MantenimientoUpdateRequest $request, Mantenimiento $mantenimiento)
    {
     $mantenimiento->vehiculo_id = $request->vehiculo_id;
     $mantenimiento->fecha_programada = $request->fecha_programada;
     $mantenimiento->fecha_realizado = $request->fecha_realizado;
     $mantenimiento->taller = $request->taller;
     $mantenimiento->descripcion_trabajo = $request->descripcion_trabajo;
     $mantenimiento->costo = $request->costo;
     $mantenimiento->kilometro_programado = $request->kilometro_programado;
     $mantenimiento->tipo_mantenimiento = $request->tipo_mantenimiento;
     $mantenimiento->kilometraje_actual = $request->kilometraje_actual;

     // Verificar si se ha subido un nuevo archivo
        if ($request->hasFile('archivo')) {
            // Eliminar el archivo anterior si existía
            if ($mantenimiento->archivo && Storage::disk('public')->exists($mantenimiento->archivo)) {
                Storage::disk('public')->delete($mantenimiento->archivo);
            }

            $uniqueName = time() . $request->file('archivo')->getClientOriginalName();
            $mantenimiento->archivo = $request->file('archivo')->storeAs('mantenimientos', $uniqueName, 'public');
        }
        $mantenimiento->save();
        return response()->json([
            'message' => 'Mantenimiento actualizado exitosamente',
            'mantenimiento' => $mantenimiento
        ], 200);
      
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        // Eliminar el archivo del sistema de archivos
        if ($mantenimiento->archivo && Storage::disk('public')->exists($mantenimiento->archivo)) {
            Storage::disk('public')->delete($mantenimiento->archivo);
        }

        // Eliminar el registro de la base de datos
        $mantenimiento->delete();

        return response()->json([
            'message' => 'Mantenimiento eliminado correctamente',
        ]);
    }
}
