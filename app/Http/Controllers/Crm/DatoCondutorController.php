<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ConductorDatoRequest;
use App\Http\Requests\Crm\UpdateDatosConuctoresRequest;
use App\Models\Crm\DatoConductor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DatoCondutorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $conductores = DatoConductor::with('user')->get();
        return response()->json([
            'success' => true,
            'data' => $conductores,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
  public function store(ConductorDatoRequest $request)
{
    try {
   //CApturar el nombre del archivo
        $nombreRut = $request->hasFile('rut_archivo') ? $request->file('rut_archivo')->getClientOriginalName() : null;
        $nombreLicencia = $request->hasFile('licencia_archivo') ? $request->file('licencia_archivo')->getClientOriginalName() : null;
        $nombreComparendo = $request->hasFile('comparendo_archivo') ? $request->file('comparendo_archivo')->getClientOriginalName() : null; 
    
        // Subir archivos si existen
        $rutArchivo = $nombreRut ? $request->file('rut_archivo')->storeAs('conductores/rut', time() . $nombreRut, 'public') : null;
        $licenciaArchivo = $nombreLicencia ? $request->file('licencia_archivo')->storeAs('conductores/licencia', time() . $nombreLicencia, 'public') : null;
        $comparendoArchivo = $nombreComparendo ? $request->file('comparendo_archivo')->storeAs('conductores/comparendo', time() . $nombreComparendo, 'public') : null;
      

        // Crear el registro del conductor
        $conductor = new \App\Models\Crm\DatoConductor([
            'user_id'             => $request->user_id,
            'cedula'              => $request->cedula,
            'licencia_conduccion' => $request->licencia_conduccion,
            'tipo_licencia'       => $request->tipo_licencia,
            'fecha_expedicion'    => $request->fecha_expedicion,
            'fecha_vencimiento'   => $request->fecha_vencimiento,
            'categoria'           => $request->categoria,
            'grupo_sanguineo'     => $request->grupo_sanguineo,
            'rut_archivo'         => $rutArchivo,
            'licencia_archivo'    => $licenciaArchivo,
            'comparendo_archivo'  => $comparendoArchivo,
        ]);

        $conductor->save();

        return response()->json([
            'success' => true,
            'message' => 'Conductor guardado correctamente',
        ], 201);
        
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al guardar el conductor',
            'error'   => $e->getMessage(),
        ], 500);
    }
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

     //Actualizar solo los archivos del conductor
    public function update(UpdateDatosConuctoresRequest $request, string $id)
    {
        $conductor= DatoConductor::findOrFail($id);
            if ($request->hasFile('rut_archivo')) {
                //Eliminar el archivo anterior si existe
       if ($conductor->rut_archivo && Storage::disk('public')->exists($conductor->rut_archivo)) {
        Storage::disk('public')->delete($conductor->rut_archivo);
    }
        $rutPath = $request->file('rut_archivo')->store('conductores/rut', 'public');
        $conductor->rut_archivo = $rutPath;
    }

    if ($request->hasFile('licencia_archivo')) {
        //Eliminar el archivo anterior si existe
        if ($conductor->licencia_archivo && Storage::disk('public')->exists($conductor->licencia_archivo)) {
            Storage::disk('public')->delete($conductor->licencia_archivo);
        }
        $licenciaPath = $request->file('licencia_archivo')->store('conductores/licencias', 'public');
        $conductor->licencia_archivo = $licenciaPath;
    }

    if ($request->hasFile('comparendo_archivo')) {

        //Eliminar el archivo anterior si existe
        if ($conductor->comparendo_archivo && Storage::disk('public')->exists($conductor->comparendo_archivo)) {
            Storage::disk('public')->delete($conductor->comparendo_archivo);
        }
        $comparendoPath = $request->file('comparendo_archivo')->store('conductores/comparendos', 'public');
        $conductor->comparendo_archivo = $comparendoPath;
    }
    // Guardar los cambios
    $conductor->save();
    return response()->json([
        'success' => true,
        'message' => 'Conductor actualizado correctamente',
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
