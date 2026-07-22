<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreHistorialGestionCarteraRequest;
use App\Services\Crm\GestionCarteraHistorialService;
use Illuminate\Http\Request;

class GestionCarteraHistorialController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $service;

    public function __construct(GestionCarteraHistorialService $service)
    {
        $this->service = $service;
    }
  public function index(Request $request)
{
    $filters = $request->only(['numero_factura', 'cliente']);
    $perPage = $request->get('per_page', 10);

    $data = $this->service->listarGestiones($filters, $perPage);


    return response()->json($data);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHistorialGestionCarteraRequest $request)
    {
        $data = $request->validated();

        $gestion = $this->service->registrarGestion(
            $data['gestion_cartera_id'],
            $data['soportes'] ?? [],
            $data['observacion'] ?? null,
            $data['tipo'],
            $data['fecha_compromiso'] ?? null
        );

        return response()->json([
            'message' => 'Gestión registrada exitosamente',
            'data' => $gestion
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //oBTENER HISTORIAL DE GESTION DE CARTERA POR ID DE GESTION DE CARTERA
        $historial = $this->service->obtenerHistorialPorGestionCartera($id);
        return response()->json([
            'data' => $historial
        ]);
    

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'soportes' => 'nullable|array',
            'soportes.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
            'observacion' => 'nullable|string',
            'tipo' => 'required|string|in:LLAMADA,EMAIL,VISITA,PROMESA_PAGO,WHATSAPP,OTRO',
            'fecha_compromiso' => 'nullable|date'
        ]);

        $gestion = $this->service->actualizarGestion(
            $id,
            $data['soportes'] ?? [],
            $data['observacion'] ?? null,
            $data['tipo'],
            $data['fecha_compromiso'] ?? null
        );

        return response()->json([
            'message' => 'Gestión actualizada exitosamente',
            'data' => $gestion
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->service->eliminarGestion($id);
        return response()->json([
            'message' => 'Gestión eliminada exitosamente'
        ]);
    }
}
