<?php

namespace App\Http\Controllers\Traslados;

use App\Http\Controllers\Controller;
use App\Http\Requests\Traslados\StoreTrasladoRequest;
use App\Http\Requests\Traslados\UpdateTrasladoBodegaRequest;
use App\Services\Traslados\TrasladoBodegaService;
use Illuminate\Http\Request;



class TrasladosBodegaController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function __construct(
        protected TrasladoBodegaService $trasladoBodegaService
    ){}
  
    public function index()
    {
        return response()->json([
            'data' => $this->trasladoBodegaService->listar()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTrasladoRequest $request)
    {
         return response()->json([
            'message' => 'Traslado creado con éxito',
            'data' => $this->trasladoBodegaService->crear($request->validated())
    ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $traslado = $this->trasladoBodegaService->getById($id);
        if (!$traslado) {
            return response()->json(['error' => 'Traslado no encontrado'], 404);
        }
        return response()->json(['data' => $traslado]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTrasladoBodegaRequest $request, string $id)
    {
        $traslado = $this->trasladoBodegaService->update($id, $request->validated());
        if (!$traslado) {
            return response()->json(['error' => 'Traslado no encontrado'], 404);
        }
        return response()->json(['message' => 'Traslado actualizado con éxito', 'data' => $traslado]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

public function aprobarPorBodega(Request $request, $id)
{
    $aprueba = $request->boolean('aprueba');
    $motivo  = $request->input('motivo');

    $trasladoId = (int) $id;

    $traslado = $this->trasladoBodegaService
        ->aprobarPorBodega($trasladoId, $aprueba, $motivo);

    return response()->json([
        'message' => $aprueba
            ? 'Traslado aprobado por bodega'
            : 'Traslado rechazado',
        'data' => $traslado,
    ]);
}




    public function rechazarPorBodega(Request $request, $id)
    {
        $motivo = $request->input('motivo');

        $trasladoId = (int) $id;

        $traslado = $this->trasladoBodegaService
            ->rechazarPorBodega($trasladoId, $motivo);

        return response()->json([
            'message' => 'Traslado rechazado por bodega',
            'data' => $traslado,
        ]);
    }
public function aprobarInventario(Request $request, $id)
{
    $trasladoId = (int) $id;

    $traslado = $this->trasladoBodegaService->aprobar($trasladoId);

    if (!$traslado) {
        return response()->json(['error' => 'Traslado no encontrado'], 404);
    }

    return response()->json([
        'message' => 'Traslado aprobado por inventario',
        'data'    => $traslado
    ]);
}



}