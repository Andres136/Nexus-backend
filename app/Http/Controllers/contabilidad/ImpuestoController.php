<?php

namespace App\Http\Controllers\contabilidad;

use App\Http\Controllers\Controller;
use App\Http\Requests\contabilidad\StoreimpuestoRequest;
use App\Services\contabilidad\ImpuestoService;
use Illuminate\Http\Request;

class ImpuestoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $impuestoService;

    public function __construct(ImpuestoService $impuestoService)
    {
        $this->impuestoService = $impuestoService;
    }
    public function index()
    {
        $impuestos = $this->impuestoService->listar();

        return response()->json([
            'data' => $impuestos
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreimpuestoRequest $request)
    {
        $impuesto = $this->impuestoService->crear($request->all());

        return response()->json([
            'message' => 'Impuesto creado correctamente',
            'data' => $impuesto
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $impuesto = $this->impuestoService->obtener($id);

        return response()->json([
            'data' => $impuesto
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreimpuestoRequest $request, string $id)
    {
        $impuesto = $this->impuestoService->actualizar($id, $request->all());

        return response()->json([
            'message' => 'Impuesto actualizado correctamente',
            'data' => $impuesto
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->impuestoService->eliminar($id);

        return response()->json([
            'message' => 'Impuesto eliminado correctamente'
        ], 200);
    }
}
