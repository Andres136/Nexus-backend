<?php

namespace App\Http\Controllers\Crm\Orden_servicio;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\Orden_servicio\StoreOrdenServicioRequest;
use App\Http\Requests\Crm\Orden_servicio\UpdateOrdenServicioRequest;
use App\Http\Requests\Crm\Orden_servicio\UpdateProcesosOrdenServicioRequest;
use App\Http\Requests\Crm\UpdateBodegaRequest;
use App\Models\Crm\Orden_servicio\OrdenServicio;
use App\Services\Crm\EntregasProveedor\EntregasService;
use App\Services\Crm\Orden_servicio\OrdenesServicioService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrdenesServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $ordenServicioService;
    protected $entregasService;


    public function __construct(OrdenesServicioService $ordenServicioService, EntregasService $entregasService)
    {
        $this->ordenServicioService = $ordenServicioService;
        $this->entregasService = $entregasService;
    }
    public function index(Request $request)
    {
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'proveedor_id', 'estado']);
        $ordenes= $this->ordenServicioService->obtenerOrdenesServicio($filtros);    
        return response()->json([
            'data' => $ordenes
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
  public function store(StoreOrdenServicioRequest $request)
{
    $data = $request->validated();

    $ordenServicio = $this->ordenServicioService->createOrdenServicio($data);

    $pdf = $this->ordenServicioService->generarPdf($ordenServicio);

    // Nombre único
    $fileName = "orden_servicio_{$ordenServicio->numero_os}.pdf";

    // Guardar en storage/app/public/ordenes_servicio
    Storage::disk('public')->put(
        "ordenes_servicio/{$fileName}",
        $pdf->output()
    );

    return response()->json([
        'message' => 'Orden creada exitosamente',
        'data' => $ordenServicio,
        'pdf_url' => asset("storage/ordenes_servicio/{$fileName}")
    ]);
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ordenServicio = $this->entregasService->obtenerEntregasPorProducto($id);
        return response()->json([
            'data' => $ordenServicio
        ]);
    }


    public function obtenerOrdenesShow(string $id)
    {
        $ordenServicio = OrdenServicio::with('empresa',
         'proveedor', 'detalles.ordenCompraDetalle.producto',
          'detalles.ordenCompraDetalle.observaciones.proceso', 
          'detalles.ordenCompraDetalle.observaciones.usuario')
            ->findOrFail($id);

        return response()->json([
            'data' => $ordenServicio
        ]);
    }   
    /**
     * Update the specified resource in storage.
     */
  public function update($id, UpdateOrdenServicioRequest $request)
{
    $orden = $this->ordenServicioService->actualizarOrdenServicio($id, $request->validated());

  $pdf = $this->ordenServicioService->generarPdf($orden);

    // Nombre único
    $fileName = "orden_servicio_{$orden->numero_os}.pdf";

    // Guardar en storage/app/public/ordenes_servicio
    Storage::disk('public')->put(
        "ordenes_servicio/{$fileName}",
        $pdf->output()
    );
    return response()->json([
        'message' => 'Orden de servicio actualizada correctamente...',
        'data' => $orden,
        'pdf_url' => asset("storage/ordenes_servicio/{$fileName}")
    ]);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
