<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\MantenimientoRequest;
use App\Http\Requests\Crm\MantenimientoUpdateRequest;
use App\Models\Crm\Mantenimiento;
use Illuminate\Http\Request;

class MantenimientoController extends Controller
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
    public function store(MantenimientoRequest $request)
    {
      //obtener el nombre del archivo
      $nombreArchivo = $request->file('archivo')->getClientOriginalName();

      $uniqueName = time() . $nombreArchivo;
        $nombreArchivo = $request->file('archivo')->storeAs('mantenimientos', $uniqueName, 'public');
 
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
        //
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
            // Obtener el nombre del nuevo archivo
            $nombreArchivo = time() . '.' . $request->archivo->getClientOriginalExtension();
            // Mover el nuevo archivo a la carpeta public/archivos
            $request->archivo->move(public_path('archivos'), $nombreArchivo);
            // Actualizar el campo archivo en la base de datos
            $mantenimiento->archivo = $nombreArchivo;
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
        //
    }
}
