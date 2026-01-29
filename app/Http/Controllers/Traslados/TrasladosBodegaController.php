<?php

namespace App\Http\Controllers\Traslados;

use App\Http\Controllers\Controller;
use App\Http\Requests\Traslados\StoreTrasladoRequest;
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

    public function aprobarPorBodega(Request $request, int $traslado)
    {
        $request->validate([
            'aprueba' => 'required|boolean',
            'motivo'  => 'nullable|string|max:500',
        ]);

        return response()->json(
            [
                'message'=>'Traslado aprobado por bodega',
                'data'=>$this->trasladoBodegaService->aprobarPorBodega(
                    $traslado,
                    $request->input('aprueba'),
                    $request->input('motivo')
                )
            ]
        );

    }

    public function rechazarPorBodega(string $id)
    {
        //
    }
    public function aprobarInventario(int $traslado)
    {
        return response()->json(
            [
                'message'=>'Traslado aprobado por inventario',
                'data'=>$this->trasladoBodegaService->aprobar($traslado)
            ]
        );

    }

}
