<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\procesoBolsasRequest;
use App\Http\Requests\Crm\StoreObservacionProcesoBolsasRequest;
use App\Models\Crm\OrdenDetalleObservaciones;
use App\Models\Crm\proceso_bolsas;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;

class procesoBolsasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $proceso_bolsas = proceso_bolsas::all();
        return response()->json($proceso_bolsas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(procesoBolsasRequest $request)
    {
        $proceso_bolsas = proceso_bolsas::create([
            'nombre' => $request->nombre,
        ]);
        return response()->json(['message' => 'Proceso de bolsa creado con éxito', 'data' => $proceso_bolsas], 201);
    }


   //Registrar observaciones para Proceso Bolsas
   public function storeObservacion(StoreObservacionProcesoBolsasRequest $request)
   {
       $observacion = OrdenDetalleObservaciones::create([
        ...$request->validated(),
        'usuario_id' => auth()->id(),
        'observacion'=> $request->observacion ?? 'Sin observaciones',
        'estado' => $request->estado ?? 'pendiente', // Asegúrate de que el estado se guarde correctamente
        
       ]);

       return response()->json(['message' => 'Observación registrada con éxito', 'data' => $observacion], 201);
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
        //
    }

    public function updateEstado(Request $request, $id)
{
    $request->validate([
        'estado' => 'required|in:pendiente,completada,en_proceso'
    ],[
        'estado.required' => 'El campo estado es obligatorio.',
        'estado.in' => 'El estado seleccionado no es válido. Los valores permitidos son: pendiente, completada, en_proceso.',
    ]);

    $observacion = OrdenDetalleObservaciones::findOrFail($id);
    $observacion->estado = $request->estado;
    $observacion->save();

    return response()->json(['success' => true, 'message' => 'Estado actualizado correctamente', 'data' => $observacion]);
}
}
