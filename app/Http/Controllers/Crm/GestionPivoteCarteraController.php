<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreGestionAbonoCarteraRequest;
use App\Services\Crm\GestionCarteraService;
use Illuminate\Http\Request;

class GestionPivoteCarteraController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $gestionCarteraPivoteService;

    public function __construct(GestionCarteraService $gestionCarteraPivoteService)
    {
        $this->gestionCarteraPivoteService = $gestionCarteraPivoteService;
    }
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGestionAbonoCarteraRequest $request)
    {
        $abonoCartera = $this->gestionCarteraPivoteService->crearAbono($request->validated()['gestion_cartera_id'], $request->validated());
        return response()->json([
            'message' => 'Abono de cartera creado exitosamente',
            'data' => $abonoCartera
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
}
