<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentoRequest;
use App\Models\Documentos;
use App\Models\Procesos;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;

class DocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        //listar todos los documentos para un usuario y a que proceso pertenecen
        $documentos = Documentos::with('usuarios','procesos')->where('proceso_id',$id)->get();
        if($documentos->isEmpty()){
            return response()->json([
                'message' => 'No hay documentos registrados'
            ], 404);
        }
        return response()->json($documentos, 200);
    }

    /**
     * Download the specified resource.
     */

     public function download($id)
     {
         // 1) Buscamos en BD el registro con el path
         $doc = Documentos::findOrFail($id);
      
     
         // 2) Construimos la ruta física absoluta
         //    $documento->documento = 'documentos/OTlWa3HQ.pdf' (ejemplo)


        // $rutaRelativa =$documento->getOriginal('documento');

       
            $filepath = storage_path('app/public/' . $doc->documento);
     
         // 3) Verificamos que exista
         if (!file_exists($filepath)) {
             return response()->json([
                 'message' => 'Documento no encontrado'
             ], 404);
         }
     
         // 4) Retornamos la descarga
         //    El segundo parámetro es el nombre que verá el usuario al descargar
         return response()->download($filepath, basename($doc->documento));
     }
      
     
     

    /**
     * Store a newly created resource in storage.
     */
    public function store(DocumentoRequest $request)
    {    
  // obtener el nombre original del archivo
        $nombre = $request->file('documento')->getClientOriginalName();
  $uniqueName= time().$nombre;
        
        $path = $request->file('documento')->storeAs('documentos',$uniqueName, 'public');
        Documentos::create([
            'nombre' => $request->nombre,
            'documento' => $path, 
            'proceso_id' => $request->proceso_id,
            'user_id' => $request->user_id,
            'version' => $request->version,
            'observaciones' => $request->observaciones,
        ]);
        return response()->json([
            'message' => 'Documento registrado correctamente'
        ], 201); 
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
       //listar todos los documentos para un usuario y a que proceso pertenecen
        $documentos = Documentos::with('usuarios','procesos')->find($id);
        if (!$documentos) {
            return response()->json(["Error" => "Documento no encontrado"], 404);
        }
        return response()->json($documentos);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $documento = Documentos::find($id);
        if (!$documento) {
            return response()->json(["Error" => "Documento no encontrado",], 404);
        }
        $documento->update($request->all());
        return response()->json("Documento actualizado correctamente", 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $documento = Documentos::find($id);
        if (!$documento) {
            return response()->json(["Error" => "Documento no encontrado"], 404);
        }
        // Eliminar el archivo físico
        Storage::disk('public')->delete($documento->documento);
        // Eliminar el registro de la base de datos
        $documento->delete();
        return response()->json([
            'message' => 'Documento eliminado correctamente'
        ], 200);
    }
public function moverAObseletos($id)
{

    $user = auth()->user();
    // 🔹 1. Buscar documento
    $documento = Documentos::findOrFail($id);

    // 🔹 2. Verificar que tenga un proceso asignado
    $procesoActual = $documento->procesos; // relación definida en el modelo Documentos
    if (!$procesoActual) {
        return response()->json(['message' => 'El documento no pertenece a ningún proceso.'], 400);
    }

    // 🔹 3. Buscar si ya existe la carpeta "Obsoletos" dentro del mismo departamento
    $carpetaObsoletos = Procesos::where('departamento_id', $procesoActual->departamento_id)
        ->whereRaw('LOWER(nombre) = ?', ['obsoletos'])
        ->first();

    // 🔹 4. Si no existe, crearla automáticamente
    if (!$carpetaObsoletos) {
        $carpetaObsoletos = Procesos::create([
            'departamento_id' => $procesoActual->departamento_id,
            'nombre' => 'Obsoletos',
            'user_id' => $user->id ?? 1, // opcional: asignar usuario actual
        ]);
    }

    // 🔹 5. Mover documento a la carpeta "Obsoletos"
    $documento->update([
        'proceso_id' => $carpetaObsoletos->id,
        'observaciones' => trim(($documento->observaciones ?? '') . 
            ' (Movido a Obsoletos el ' . now()->format('d/m/Y H:i') . ')'),
    ]);



    // 🔹 7. Responder con datos actualizados
    return response()->json([
        'message' => 'Documento movido correctamente a la carpeta "Obsoletos".',
        'carpeta_obsoletos' => $carpetaObsoletos,
        'documento_actualizado' => $documento,
    ], 200);
}

}
