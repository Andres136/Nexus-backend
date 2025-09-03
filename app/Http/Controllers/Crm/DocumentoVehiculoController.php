<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\DocumentoVehiculoRequest;
use App\Models\Crm\DocumentoVehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentoVehiculoController extends Controller
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
    public function store(DocumentoVehiculoRequest $request)
    {
        // obtener el nombre del archivo
        $nombreArchivo = $request->file('documento_pdf')->getClientOriginalName();
        $uniqueName = time() . $nombreArchivo;
        $rutaArchivo = $request->file('documento_pdf')->storeAs('documentos_vehiculos', $uniqueName, 'public');
 
        // crear un nuevo registro en la base de datos
        $documentos=DocumentoVehiculo::create([
            'vehiculo_id' => $request->vehiculo_id,
            'tipo_documento' => $request->tipo_documento,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'fecha_renovacion' => $request->fecha_renovacion,
            'documento_pdf' => $rutaArchivo,
            'estado' => $request->estado,

        ]);
        // guardar el registro
        $documentos->save();
        // retornar una respuesta
        return response()->json([
            'success' => true,
            'message' => 'Documento guardado correctamente',
        ]);
   
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
    public function update(DocumentoVehiculoRequest $request, string $id)
    {
       

        // Buscar el documento por ID
        $documento = DocumentoVehiculo::findOrFail($id);

        // Actualizar los campos del documento
        $documento->vehiculo_id = $request->vehiculo_id;
        $documento->tipo_documento = $request->tipo_documento;
        $documento->fecha_vencimiento = $request->fecha_vencimiento;
        $documento->fecha_renovacion = $request->fecha_renovacion;
        if ($request->hasFile('documento_pdf')) {
            // Obtener el nombre del archivo
            $nombreArchivo = $request->file('documento_pdf')->getClientOriginalName();
            $uniqueName = time() . $nombreArchivo;
            // Almacenar el archivo
            $rutaArchivo = $request->file('documento_pdf')->storeAs('documentos_vehiculos', $uniqueName, 'public');
            $documento->documento_pdf = $rutaArchivo;
        }
        $documento->estado = $request->estado;

        // Guardar los cambios
        $documento->save();

        return response()->json([
            'success' => true,
            'message' => 'Documento actualizado correctamente',
        ]);
    }

public function actualizarFechas(Request $request, string $id)
{
    $request->validate([
        'fecha_vencimiento' => 'required|date',
        'fecha_renovacion' => 'nullable|date',
        'documento_pdf' => 'nullable|file|mimes:pdf|max:5120', // máx 5MB
    ]);

    $documento = DocumentoVehiculo::findOrFail($id);

    $documento->fecha_vencimiento = $request->fecha_vencimiento;
    $documento->fecha_renovacion = $request->fecha_renovacion;

    // Si hay archivo, reemplazar
    if ($request->hasFile('documento_pdf')) {
        // Elimina el archivo anterior si existe
        if ($documento->documento_pdf && Storage::disk('public')->exists($documento->documento_pdf)) {
            Storage::disk('public')->delete($documento->documento_pdf);
        }

        $path = $request->file('documento_pdf')->store('documentos', 'public');
        $documento->documento_pdf = $path;
    }

    $documento->save();

    return response()->json([
        'success' => true,
        'message' => 'Fechas del documento actualizadas correctamente',
    ]);
}



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $documento = DocumentoVehiculo::findOrFail($id);

        // Eliminar el archivo del sistema de archivos
        if ($documento->documento_pdf && Storage::disk('public')->exists($documento->documento_pdf)) {
            Storage::disk('public')->delete($documento->documento_pdf);
        }

        // Eliminar el registro de la base de datos
        $documento->delete();

        return response()->json([
            'success' => true,
            'message' => 'Documento eliminado correctamente',
        ]);
    }
}
