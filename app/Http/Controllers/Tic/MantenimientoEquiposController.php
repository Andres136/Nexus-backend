<?php

namespace App\Http\Controllers\Tic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tic\CambiarEstadoRequest;
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

  public function obtenerMantenimientos()
    {
        $filters = request()->only(['sede_id', 'producto_id', 'empresa_id', 'tipo', 'estado']);
        $mantenimientos = $this->mantenimientoEquiposService->listarMantenimientos($filters);
        return response()->json($mantenimientos);
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
  public function update(CambiarEstadoRequest $request, string $id)
{
    $data = $request->validated();

    $mantenimiento = $this->mantenimientoEquiposService->cambiarEstado(
        $id,
        $data,
        $request->file('archivos') // 👈 ahora es array
    );

    return response()->json([
        'message' => 'Estado del mantenimiento actualizado exitosamente',
        'data' => $mantenimiento,
    
    ]);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CambiarEstadoRequest $request, string $id)
    {
        $data = $request->validated();

    $mantenimiento = $this->mantenimientoEquiposService->cambiarEstado(
        $id,
        $data,
        $request->file('archivos') // 👈 ahora es array
    );

    return response()->json([
        'message' => 'Estado del mantenimiento actualizado exitosamente',
        'data' => $mantenimiento,
    
    ]);
    }


    //Actualizar mantenimiento
    public function actualizarMantenimiento(Request $request, string $id)
    {
        $data = $request->all();

        $mantenimiento = $this->mantenimientoEquiposService->actualizarMantenimiento($id, $data);

        return response()->json([
            'message' => 'Mantenimiento actualizado exitosamente',
            'data' => $mantenimiento
        ]);
    }

}
