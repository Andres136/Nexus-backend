<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreGestionCarteraRequest;
use App\Http\Requests\Crm\UpdateGestionCarteraRequest;
use App\Services\Crm\GestionCarteraService;
use Illuminate\Http\Request;

class GestionCarteraController extends Controller
{
    /**
     * Display a listing of the resource.
     */

   protected $gestionCarteraService;

   public function __construct(GestionCarteraService $gestionCarteraService)
   {
         $this->gestionCarteraService = $gestionCarteraService;
   }

    public function index(Request $request)
    {
        $filtros = $request->only(['buscar', 'fecha_inicio', 'fecha_fin', 'cliente_id', 'user_comercial_id', 'estado', 'per_page']);
        $data = $this->gestionCarteraService->listarGestionCartera($filtros);
        return response()->json([
            'message' => 'Gestión de cartera obtenida exitosamente',
            'data' => $data['paginator'],
            'total' => $data['total_cartera']
            
            
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGestionCarteraRequest $request)
    {
        $gestionCartera = $this->gestionCarteraService->crearGestionCartera($request->validated());
        return response()->json([
            'message' => 'Factura registrada exitosamente',
            'data' => $gestionCartera
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $gestionCartera = $this->gestionCarteraService->find($id);
        return response()->json([
            'message' => 'Factura obtenida exitosamente',
            'data' => $gestionCartera
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGestionCarteraRequest $request, string $id)
    {
        $updatedGestionCartera = $this->gestionCarteraService->update($id, $request->validated());
        return response()->json([
            'message' => 'Factura actualizada exitosamente',
            'data' => $updatedGestionCartera
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
   public function destroy($id)
{
    $cartera = $this->gestionCarteraService->cancelarDeuda($id);

    return response()->json([
        'message' => 'Deuda cancelada correctamente',
        'data' => $cartera
    ]);
}


public function estadisticasCartera(Request $request)
{
    $year = $request->query('year', now()->year);
    $lineaTiempo = $this->gestionCarteraService->lineaTiempoAnual($year);

    return response()->json([
        'message' => 'Línea de tiempo anual obtenida exitosamente',
        'data' => $lineaTiempo
    ]);
}
}