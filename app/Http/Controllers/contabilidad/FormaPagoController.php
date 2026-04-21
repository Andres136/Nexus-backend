<?php

namespace App\Http\Controllers\contabilidad;

use App\Http\Controllers\Controller;
use App\Http\Requests\contabilidad\StoreFormaPagoRequest;
use App\Services\contabilidad\FormaPagoService;
use Illuminate\Http\Request;

class FormaPagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $formaPagoService;

    public function __construct(FormaPagoService $formaPagoService)
    {
        $this->formaPagoService = $formaPagoService;
    }
    public function index()
    {
        $formasPago = $this->formaPagoService->listar();

        return response()->json([
            'data' => $formasPago
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFormaPagoRequest $request)
    {
            $formaPago = $this->formaPagoService->crear($request->all());

            return response()->json([
                'message' => 'Forma de pago creada correctamente',
                'data' => $formaPago
            ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $formaPago = $this->formaPagoService->obtener($id);

        return response()->json([
            'data' => $formaPago
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, $id)
{
    $formaPago = $this->formaPagoService->actualizar((int) $id, $request->all());

    return response()->json([
        'message' => 'Forma de pago actualizada correctamente',
        'data' => $formaPago
    ], 200);
}
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->formaPagoService->eliminar($id);

        return response()->json([
            'message' => 'Forma de pago eliminada correctamente'
        ], 200);
    }
}
