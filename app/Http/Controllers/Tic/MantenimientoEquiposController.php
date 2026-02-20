<?php

namespace App\Http\Controllers\Tic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tic\StoreMantenimientoEquiposRequest;
use App\Services\Tic\MantenimientoEquiposService;
use Illuminate\Http\Request;

class MantenimientoEquiposController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $mantenimientoEquiposService;
     public function __construct(MantenimientoEquiposService $mantenimientoEquiposService)
    {
        $this->mantenimientoEquiposService = $mantenimientoEquiposService;
    }
    public function index()
    {
        $mantenimientos = $this->mantenimientoEquiposService->obtenerMantenimientos();
        return response()->json($mantenimientos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMantenimientoEquiposRequest $request)
    {
        $data = $request->validated();
        $mantenimiento = $this->mantenimientoEquiposService->registrarMantenimiento($data);
        return response()->json([
            'message' => 'Mantenimiento registrado exitosamente',
            'data' => $mantenimiento
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
