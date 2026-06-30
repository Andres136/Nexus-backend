<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\DocumentosAdministrativoReques;
use App\Http\Requests\Crm\DocumentosAdministrativoRequest;
use App\Models\Crm\Carpeta;
use App\Models\Crm\Documentos_Administrativos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentosAdministrativosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //trear todos los documentos administrativos registrados asociados a una carpeta
        $documentos = Carpeta::with('documentos')->get();
        if($documentos->isEmpty()){
            return response()->json([
                'message' => 'No hay documentos registrados'
            ], 404);
        }
        return response()->json($documentos, 200);

    }

    /**
     * Show the form for creating a new resource.
     */
   
    /**
     * Store a newly created resource in storage.
     */
    public function store(DocumentosAdministrativoRequest $request)
    {
           // obtener el nombre original del archivo
        $nombre = $request->file('archivo')->getClientOriginalName();
        $uniqueName= time().$nombre;
        $path = $request->file('archivo')->storeAs('documentos',$uniqueName, 'public');
        Documentos_Administrativos::create([
            'nombre' => $request->nombre,
            'archivo' => $path,
            'carpeta_id' => $request->carpeta_id,
            'usuario_id' => auth()->user()->id 
            
        ]);
        return response()->json([
            'message' => 'Documento registrado correctamente'
        ], 201);     
    }

    /**
     * Display the specified resource.
     */

     public function show($id)
     {
         $perPage = min(max((int) request('per_page', 10), 1), 50);

         return response()->json(
             Documentos_Administrativos::where('carpeta_id', $id)
                 ->orderByDesc('created_at')
                 ->paginate($perPage),
             200
         );
     }
     
    public function downloand( $id)
    {

    $documentos = Documentos_Administrativos::findOrFail($id);

    $filepath = storage_path('app/public/' . $documentos->archivo);
    if (!file_exists($filepath)) {
        return response()->json([
            'message' => 'Documento no encontrado'
        ], 404);

        

    }
return response()->download($filepath, basename($documentos->archivo));

}

//Eliminar un documento administrativo
public function destroy($id)
{
    $documento = Documentos_Administrativos::findOrFail($id);
    // Eliminar el archivo físico
    Storage::disk('public')->delete($documento->archivo);
    $documento->delete();
    return response()->json([
        'message' => 'Documento eliminado correctamente'
    ], 200);
}
}
