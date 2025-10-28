<?php

namespace App\Http\Controllers\Traslados;

use App\Http\Controllers\Controller;
use App\Http\Requests\Traslados\EnvioRequest;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\Sede;
use App\Services\Crm\InventarioService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnvioInternoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $inventarioService;
  public function __construct(InventarioService $inventarioService)
  {
      $this->inventarioService = $inventarioService;
  }
      
  
  
  public function index(): JsonResponse
    {
        $envios = \App\Models\Traslados\Envio_internos::with(['detalles.producto', 'detalles.envio'])
            ->orderBy('id', 'desc')
            ->paginate(15);

        return response()->json($envios);
    }


   
    /**
     * Store a newly created resource in storage.
     */
    public function store(EnvioRequest $request)
    {
       $user = auth()->user();
         $data = $request->all();

        $resultado = $this->inventarioService->registrarEnvioConDescuento($data, $user);

        //Generar pd del envio automaticamente

        return response()->json($resultado, $resultado['success'] ? 201 : 500);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $envio = \App\Models\Traslados\Envio_internos::with([
            'detalles.producto:id,name,code',
            'detalles.ordenCompra:id,numero_orden,fecha',
            'usuario:id,name',
            'sedeOrigen:id,nombre',
            'sedeDestino:id,nombre'
        ])->find($id);

        if (!$envio) {
            return response()->json([
                'success' => false,
                'message' => 'Envío no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'envio' => $envio
        ]);
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


    //Traer Sedes para el envio
    public function traerSedes(): JsonResponse
    {
        $sedes = Sede::select('id', 'nombre')
            ->get();
        return response()->json($sedes);
    }
    //Traer Ordenes de Compra para el envio
    public function traerOrdenesCompra(): JsonResponse
    {
        $ordenes = OrdenCompraProveedor::select('id', 'numero_orden', 'fecha')
             ->latest()          // orden reciente primero
        ->limit(50)         // por ejemplo 50
        ->get();
        return response()->json($ordenes);
    
    }
}