<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\InspeccionesRequest;
use App\Models\Crm\Inspeccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

            //traer el nombre original del archivo
            $nombre = $request->file('documento')->getClientOriginalName();
            $uniqueName = time() . $nombre;
            //subir el archivo y almacenar su ruta
            $rutaDocumento = $request->file('documento')->storeAs('inspecciones', $uniqueName, 'public');
    

        $inspeccion = Inspeccion::create([
            'vehiculo_id' => $request->vehiculo_id,
            'fecha' => $request->fecha,
            'responsable' => $request->responsable,
            'observaciones' => $request->observaciones,
            'estado_general' => $request->estado_general,
            'documento' => $rutaDocumento,
            

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

        // Verificar si se ha subido un nuevo documento
        if ($request->hasFile('documento')) {
            // Obtener el nombre original del archivo
            $nombre = $request->file('documento')->getClientOriginalName();
            $uniqueName = time() . $nombre;
            // Subir el archivo y almacenar su ruta
            $rutaDocumento = $request->file('documento')->storeAs('inspecciones', $uniqueName, 'public');
            $inspeccion->documento = $rutaDocumento;
        }

        // Actualizar otros campos
        $inspeccion->vehiculo_id = $request->vehiculo_id;
        $inspeccion->fecha = $request->fecha;
        $inspeccion->responsable = $request->responsable;
        $inspeccion->observaciones = $request->observaciones;
        $inspeccion->estado_general = $request->estado_general;

        // Guardar los cambios
        $inspeccion->save();

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
        $inspeccion = Inspeccion::findOrFail($id);

        // Eliminar el archivo del sistema de archivos
        if ($inspeccion->documento && Storage::disk('public')->exists($inspeccion->documento)) {
            Storage::disk('public')->delete($inspeccion->documento);
        }

        // Eliminar el registro de la base de datos
        $inspeccion->delete();

        return response()->json([
            'message' => 'Inspección eliminada correctamente',
        ]);
    }
}
