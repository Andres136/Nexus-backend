<?php

namespace App\Http\Controllers\Traslados;

use App\EstadoEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Traslados\EnvioRequest;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use App\Models\Crm\Sede;
use App\Models\Traslados\Envio_internos;
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
      
  
  
  public function index(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'sede_origen_id' => 'nullable|integer|exists:sedes,id',
            'sede_destino_id' => 'nullable|integer|exists:sedes,id',
            'empresa_id' => 'nullable|integer|exists:empresas,id',
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = auth()->user();

        $query = Envio_internos::query()
            ->with([
                'sedeOrigen:id,nombre',
                'sedeDestino:id,nombre',
                'empresa:id,nombre',
                'usuario:id,name',
                'detalles.product:id,name,code',
                'detalles.bodegaOrigen:id,nombre',
                'detalles.ordenCompra:id,numero_orden',
            ])
            ->withCount('detalles')
            ->withSum('detalles as cantidad_total', 'cantidad');

        if (!in_array($user->role_id, [1, 2, 4]) && $user->sede_id) {
            $query->where(function ($q) use ($user) {
                $q->where('sede_origen_id', $user->sede_id)
                    ->orWhere('sede_destino_id', $user->sede_id);
            });
        }

        $query
            ->when($request->filled('fecha_inicio'), fn ($q) =>
                $q->whereDate('fecha_envio', '>=', $request->fecha_inicio)
            )
            ->when($request->filled('fecha_fin'), fn ($q) =>
                $q->whereDate('fecha_envio', '<=', $request->fecha_fin)
            )
            ->when($request->filled('sede_origen_id'), fn ($q) =>
                $q->where('sede_origen_id', $request->sede_origen_id)
            )
            ->when($request->filled('sede_destino_id'), fn ($q) =>
                $q->where('sede_destino_id', $request->sede_destino_id)
            )
            ->when($request->filled('empresa_id'), fn ($q) =>
                $q->where('empresa_id', $request->empresa_id)
            )
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);

                $q->where(function ($subQuery) use ($search) {
                    if (ctype_digit($search)) {
                        $subQuery->where('id', (int) $search);
                    }

                    $method = ctype_digit($search) ? 'orWhere' : 'where';
                    $subQuery->{$method}('notas', 'LIKE', "%{$search}%")
                        ->orWhereHas('detalles.ordenCompra', fn ($orderQuery) =>
                            $orderQuery->where('numero_orden', 'LIKE', "%{$search}%")
                        )
                        ->orWhereHas('detalles.product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('code', 'LIKE', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('fecha_envio')
            ->orderByDesc('id');

        return response()->json(
            $query->paginate($request->integer('per_page', 15))->withQueryString()
        );
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
        $envio = Envio_internos::with([
            'detalles.product:id,name,code',
            'detalles.ordenCompra:id,numero_orden,fecha',
            'detalles.bodegaOrigen:id,nombre',
            'usuario:id,name',
            'sedeOrigen:id,nombre',
            'sedeDestino:id,nombre',
            'empresa:id,nombre',
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
        $data = $request->validate([
            'fecha_envio' => 'required|date',
            'sede_destino_id' => 'required|integer|exists:sedes,id',
            'empresa_id' => 'required|integer|exists:empresas,id',
            'notas' => 'nullable|string|max:500',
        ]);

        $envio = Envio_internos::findOrFail($id);
        $user = auth()->user();

        if (
            !in_array($user->role_id, [1, 2, 4]) &&
            (int) $envio->sede_origen_id !== (int) $user->sede_id
        ) {
            abort(403, 'No tiene permiso para editar este traslado.');
        }

        if ((int) $data['sede_destino_id'] === (int) $envio->sede_origen_id) {
            return response()->json([
                'message' => 'La sede destino debe ser diferente a la sede origen.',
                'errors' => [
                    'sede_destino_id' => [
                        'La sede destino debe ser diferente a la sede origen.',
                    ],
                ],
            ], 422);
        }

        DB::transaction(function () use ($envio, $data) {
            $envio->update($data);

            DB::table('movimientos_stock')
                ->where('envio_interno_id', $envio->id)
                ->update([
                    'sede_destino_id' => $data['sede_destino_id'],
                    'updated_at' => now(),
                ]);

            $this->inventarioService->regenerarPdfEnvio($envio);
        });

        return response()->json([
            'message' => 'Traslado actualizado correctamente.',
            'envio' => $envio->load([
                'sedeOrigen:id,nombre',
                'sedeDestino:id,nombre',
                'empresa:id,nombre',
                'usuario:id,name',
                'detalles.product:id,name,code',
                'detalles.bodegaOrigen:id,nombre',
                'detalles.ordenCompra:id,numero_orden',
            ]),
        ]);
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
  public function traerOrdenesCompra(Request $request): JsonResponse
{
    $q = $request->input('q'); // ← ESTA ES LA CORRECCIÓN

    $query = OrdenCompraProveedor::select('id', 'numero_orden', 'fecha')
        ->orderBy('id', 'desc');

    // Si hay búsqueda → traer coincidencias
    if ($q) {
        $query->where('numero_orden', 'LIKE', "%{$q}%");
        return response()->json($query->get());
    }

    // Si no hay búsqueda → traer solo las 50 más recientes
    return response()->json(
        $query->limit(50)->get()
    );
}

public function mostrarOC($id)
{
    return OrdenCompraProveedor::select('id','numero_orden','fecha')
        ->findOrFail($id);
}
//Crear una funcion que reciba como fitro el  id del producto y traiga ordenes de compra provvedor  pendiente segun la sede destino
public function traerOrdenesCompraPendientes(Request $request): JsonResponse
{
    $request->validate([
        'producto_id' => 'required|integer',
        'sede_id'     => 'required|integer',
    ]);

    $ordenes = OrdenCompraProveedor::query()
        ->where('sede_id', $request->sede_id)   // 👈 sede en la orden
        ->whereIn('estado_id', [
            EstadoEnum::PENDIENTE->value,
            EstadoEnum::ENTREGA_PARCIAL->value,
        ])
        ->whereHas('detalles', function ($q) use ($request) {
            $q->where('producto_id', $request->producto_id)
                ->whereRaw(
                    'COALESCE(cantidad_entregada, 0) < cantidad_solicitada'
                );
        })
        ->with([
            'detalles' => function ($q) use ($request) {
                $q->where('producto_id', $request->producto_id)
                    ->whereRaw(
                        'COALESCE(cantidad_entregada, 0) < cantidad_solicitada'
                    );
            },
            'proveedor:id,nombre',
        ])
        ->orderBy('fecha')
        ->get();

    return response()->json($ordenes);
}




}
