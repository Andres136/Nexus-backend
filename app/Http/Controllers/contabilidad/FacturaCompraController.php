<?php

namespace App\Http\Controllers\contabilidad;

use App\Http\Controllers\Controller;
use App\Http\Requests\contabilidad\RegistrarPagoRequest;
use App\Http\Requests\contabilidad\StoreFacturaCompreRequest;
use App\Services\contabilidad\FacturaCompraService;
use Illuminate\Http\Request;

class FacturaCompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $facturaCompraService;

    public function __construct(FacturaCompraService $facturaCompraService)
    {
        $this->facturaCompraService = $facturaCompraService;
    }
    public function index( Request $request)
    {
        $filters = $request->only(['proveedor_id', 'fecha_inicio', 'fecha_fin', 'estado_id', 'search']);
        $facturas = $this->facturaCompraService->listar($filters);

        return response()->json([
            'message' => 'Facturas obtenidas correctamente',
            'data' => $facturas
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFacturaCompreRequest $request)
    {
          $factura = $this->facturaCompraService->crear($request->all());

        return response()->json([
            'message' => 'Factura creada correctamente',
            'data' => $factura
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $factura = $this->facturaCompraService->obtenerDetalles((int)$id);

        return response()->json([
            'message' => 'Factura obtenida correctamente',
                   'forma_pago_id' => optional($factura->pagos->first())->forma_pago_id,
            'data' => $factura
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->all();
        $factura = $this->facturaCompraService->actualizar((int)$id, $data);
        return response()->json([
            'message' => 'Factura actualizada correctamente',
            'data' => $factura
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->facturaCompraService->anular((int)$id);
        return response()->json([
            'message' => 'Factura Anulada correctamente',
        ], 200);
    }


}
