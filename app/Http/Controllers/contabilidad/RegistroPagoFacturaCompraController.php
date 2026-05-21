<?php

namespace App\Http\Controllers\contabilidad;

use App\Http\Controllers\Controller;
use App\Http\Requests\contabilidad\RegistrarPagoRequest;
use App\Services\contabilidad\RegistroPagoFacturaCompraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RegistroPagoFacturaCompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $facturaCompraService;

    public function __construct(RegistroPagoFacturaCompraService $facturaCompraService)
    {
        $this->facturaCompraService = $facturaCompraService;
    }
   
    public function index( Request $request)
    {
        $filters = $request->only(['proveedor_id', 'fecha_inicio', 'fecha_fin', 'estado_id', 'search']);
        $facturas = $this->facturaCompraService->listar($filters);

        return response()->json([
            'message' => 'Pagos obtenidos correctamente',
            'data' => $facturas
        ], 200);
    }

     /**
     * Registrar un pago a una factura de compra
     */

    /**
     * Store a newly created resource in storage.
     */
public function store(RegistrarPagoRequest $request, int $facturaId)
{
    try {

        $resultado = $this->facturaCompraService->registrarPago(
            $facturaId,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Pago registrado correctamente.',
            'data' => $resultado
        ], 201);

    } catch (\Exception $e) {

        Log::error('Error al registrar pago factura', [
            'factura_id' => $facturaId,
            'request' => $request->all(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error al registrar el pago.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
            $factura = $this->facturaCompraService->obtenerDetalles((int)$id);
    
            return response()->json([
                'message' => 'Factura obtenida correctamente',
                'data' => $factura
            ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $pagos = $this->facturaCompraService->actualizarPago((int)$id, $request->all());
        return response()->json([
            'message' => 'Pago actualizado correctamente',
            'data' => $pagos
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
