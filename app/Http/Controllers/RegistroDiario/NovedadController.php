<?php

namespace App\Http\Controllers\RegistroDiario;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calidad\StoreUpdateNovedadesRequest;
use App\Services\RegistroDiario\NovedadService;
use Illuminate\Http\Request;

class NovedadController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $novedadService;

    public function __construct(NovedadService $novedadService)
    {
        $this->novedadService = $novedadService;
    }
   public function index(Request $request)
{
    $filters = $request->only([
        'fecha_inicio',
        'fecha_fin',
        'departamento_id',
        'search',
        'usuario',
        'per_page',
        'page'
    ]);

    $novedades = $this->novedadService->getNovedades($filters);

    return response()->json($novedades);
}


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
       $query = $this->novedadService->getNovedadesById($id);
       return response()->json($query);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreUpdateNovedadesRequest $request, string $id)
    {$query = $this->novedadService->updateNovedad(
    $id,
    $request->except('soporte'),
    $request
);
        return response()->json([
            'message' => 'Novedad actualizada exitosamente',
            'data' => $query
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deleted = $this->novedadService->deleteNovedad($id);
        if (!$deleted) {
            return response()->json([
                'message' => 'Novedad no encontrada'
            ], 404);
        }
        return response()->json([
            'message' => 'Novedad eliminada exitosamente'
        ]);
    }
}
