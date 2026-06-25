<?php

namespace App\Http\Controllers\Crm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\OrdenCompraProveedorRequest;
use App\Http\Requests\Crm\UpdateOrdenCompraProveedorDetallesRequest;
use App\Http\Requests\Crm\UpdateOrdenProveedorRequest;
use App\Mail\OrdenCompraProveedorMail;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use App\Models\Crm\OrdenCompraProveedorDetalleOrigen;
use App\Models\Crm\OrdenDetalleObservaciones;
use App\Services\Crm\OrdenCompraService;
use Barryvdh\DomPDF\Facade\Pdf ;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrdenCompraProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */

public function index(Request $request,OrdenCompraService $estadoService)
{
    $user = auth()->user();
    
    $query = OrdenCompraProveedor::with([
        'proveedor',
        'usuario',
        'estado',
        'detalles.entregas' => function ($query) use ($user) {
            // ✅ Filtrar entregas según el rol del usuario
            if (!in_array($user->role_id, [1, 2, 4])) {
                $query->where('sede_id', $user->sede_id);
            }
            // Los admins ven todas las entregas
        },
        'detalles.procesoBolsas',
        'detalles.proveedor',
        'detalles.origenes.ordenCompra',
        'detalles.origenes.ordenCompraDetalle',
        'empresa',
        'sede'
    ])
    ->when(!in_array($user->role_id, [1, 2, 4]), function ($q) use ($user) {
        if (!is_null($user->sede_id)) {
            $q->where('sede_id', $user->sede_id); // Solo su sede para no-admins
        }
    })
    ->when(in_array($user->role_id, [1, 2, 4]), function ($q) {
        // Los admins ven todo (incluyendo NULL)
    })

    ->when($request->filled('proveedor_id'), function ($q) use ($request) {
        $q->where('proveedor_id', $request->proveedor_id);
    })
    ->orderByRaw("
        CASE
            WHEN (
                SELECT SUM(d.cantidad_entregada)
                FROM orden_compra_proveedor_detalles d
                WHERE d.orden_id = orden_compra_proveedores.id
            ) = 0 OR (
                SELECT COUNT(*) FROM orden_compra_proveedor_detalles d2
                WHERE d2.orden_id = orden_compra_proveedores.id
            ) = 0 THEN 0
            WHEN (
                SELECT COUNT(*)
                FROM orden_compra_proveedor_detalles d3
                WHERE d3.orden_id = orden_compra_proveedores.id
                AND d3.cantidad_entregada < d3.cantidad_solicitada
            ) > 0 THEN 1
            ELSE 2
        END
    ")

    ->orderByRaw("CASE WHEN sede_id = ? THEN 0 ELSE 1 END", [$user->sede_id ?? 0])
    ->orderBy('id', 'desc');

    if ($request->has('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('numero_orden', 'LIKE', "%$search%")
                ->orWhereHas('proveedor', function ($q) use ($search) {
                    $q->where('nombre', 'LIKE', "%$search%");
                })
                ->orWhereHas('sede', function ($q) use ($search) {
                    $q->where('nombre', 'LIKE', "%$search%");
                });
        });
    }
    // 🔍 FILTRO POR SEMANA (formato: 2025-W48)


if ($request->filled('fecha_inicio') || $request->filled('fecha_fin')) {

    $fechaInicio = $request->filled('fecha_inicio') 
        ? Carbon::parse($request->fecha_inicio)->startOfDay()
        : null;

    $fechaFin = $request->filled('fecha_fin') 
        ? Carbon::parse($request->fecha_fin)->endOfDay()
        : null;

    $query->when($fechaInicio && $fechaFin, function ($q) use ($fechaInicio, $fechaFin) {
        $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    })
    ->when($fechaInicio && !$fechaFin, function ($q) use ($fechaInicio) {
        $q->where('fecha', '>=', $fechaInicio);
    })
    ->when(!$fechaInicio && $fechaFin, function ($q) use ($fechaFin) {
        $q->where('fecha', '<=', $fechaFin);
    });
}

    $perPage = min(max($request->integer('per_page', 10), 1), 100);
    $ordenes = $query->paginate($perPage);

 
    $ordenes= $estadoService->procesar($ordenes, $user);

    return response()->json([
        'message' => 'Lista paginada de órdenes de compra',
        'ordenes' => $ordenes,
        'usuario_contexto' => [
            'id' => $user->id,
            'nombre' => $user->name,
            'sede_id' => $user->sede_id,
            'sede_nombre' => $user->sede->nombre ?? null,
            'es_admin' => in_array($user->role_id, [1, 2, 4]),
            'vista_descripcion' => !in_array($user->role_id, [1, 2, 4]) 
                ? 'Vista filtrada por su sede' 
                : ($user->sede_id ? 'Vista de administrador con sede asignada' : 'Vista global de administrador')
        ]
    ], 200);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(OrdenCompraProveedorRequest $request)
    {

        $user = auth()->user();

         // Validar que la sede y bodega (si se proporciona) pertenezcan a la empresa seleccionada
         if ($request->sede_id) {
            $sedeValida = DB::table('sedes')
                ->where('id', $request->sede_id)
                ->where('empresa_id', $request->empresa_id)
                ->exists();
            if (!$sedeValida) {
                return response()->json(['error' => 'La sede seleccionada no pertenece a la empresa indicada.'], 422);
            }
        }
        DB::beginTransaction();
        // Generar número de orden consecutivo
        $ultimaOrden = OrdenCompraProveedor::orderBy('id', 'desc')->first();
        $numeroConsecutivo = $ultimaOrden ? $ultimaOrden->id + 1 : 1;
        $numeroOrden = 'OC-' . str_pad($numeroConsecutivo, 3, '0', STR_PAD_LEFT);
        try {
            $ordenCompra = OrdenCompraProveedor::create([
                'proveedor_id' => $request->proveedor_id,
                'fecha' =>now() ,
                'numero_orden' => $numeroOrden,
                'estado_id' => 1, // Estado inicial
                'usuario_id' => auth()->id(),
                // 'usuario_id' => $request->usuario_id, // Si se desea permitir la asignación de un usuario diferente
                'observaciones' => $request->observaciones,
                'empresa_id' => $request->empresa_id,
                'bodega_id' => $request->bodega_id,
                'sede_id' => $user->sede_id ?? $request->sede_id, // Asignar la sede del usuario autenticado si no es admin
                'fecha_entrega' => $request->fecha_entrega,
            ]);

            foreach ($request->detalles as $i => $detalle) {
             $nuevoDetalle=   $ordenCompra->detalles()->create([
                    'item' => $i + 1,
                    'descripcion' => $detalle['descripcion'],
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'cantidad_entregada' => $detalle['cantidad_entregada'] ?? 0,
                    'proveedor_id' => $detalle['proveedor_id'] ?? null, // Aseguramos que este campo sea nullable
                    'proceso_bolsas_id' => $detalle['proceso_bolsas_id'] ?? null, // Aseguramos que este campo sea nullable
                    'code' => $detalle['code'] ?? null, // Nuevo campo código
                    'producto_id' => $detalle['producto_id'] ?? null, // Nuevo campo producto_id
                ]);
                $this->crearOrigenesDetalleProveedor(
                    $nuevoDetalle,
                    $detalle['origenes'] ?? [],
                    $ordenCompra
                );
   if (!empty($detalle['procesos'])) {
        foreach ($detalle['procesos'] as $proceso) {
            OrdenDetalleObservaciones::create([
                'orden_detalle_id' =>$nuevoDetalle->id, // Asegúrate de que el detalle ya tenga un ID asignado
                'proceso_bolsas_id' => $proceso['proceso_bolsas_id'],
                'proveedor_id' => $proceso['proveedor_id'],
                'observacion' => $proceso['observacion'] ?? 'Proceso registrado sin observación',
                'estado' => 'pendiente',
                'usuario_id' => auth()->id(),
            ]);
        }
    }

            }

            DB::commit();
            return $this->show($ordenCompra->id);

            return response()->json(['message' => 'Orden de compra creada con éxito.',
               'orden' => $ordenCompra
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al crear la orden de compra.',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }




    /**
     * Display the specified resource.
     */      //Consultar una orden y su estado actual (con detalles y análisis de cantidades entregadas vs solicitadas).
 public function show($id)
{
    $user = auth()->user();

    $orden = OrdenCompraProveedor::with([
        'proveedor',
        'usuario',
        'estado',
        'detalles.entregas' => function ($query) use ($user) {
            if (!in_array($user->role_id, [1, 4])) {
                $query->where('sede_id', $user->sede_id);
            }

            $query->with([
                'usuario:id,name',
                'bodega:id,nombre,sede_id',
                'bodega.sede:id,nombre'
            ]);
        },
        'detalles.procesoBolsas',
        'detalles.proveedor',
        'empresa',
        'detalles.producto',
        'sede'
    ])->findOrFail($id);

    $detalles = $orden->detalles->map(function ($detalle) use ($user) {

        // Total histórico (siempre existe)
        $entregadoGlobal = (float) $detalle->cantidad_entregada;

        // Entregas visibles según filtro aplicado en WITH
        $entregasFiltradas = $detalle->entregas;

        // Cantidad entregada por sede
        if (!in_array($user->role_id, [1, 2])) {
            $entregadoSede = $entregasFiltradas->sum('cantidad_entregada');
        } else {
            $entregadoSede = $detalle->entregas
                ->where('sede_id', $user->sede_id)
                ->sum('cantidad_entregada');
        }

        //  FALLBACK para órdenes viejas sin sede_id
        $tieneSede = $detalle->entregas->whereNotNull('sede_id')->count() > 0;

        if (!$tieneSede && $detalle->entregas->count() > 0) {
            $entregadoSede = $entregadoGlobal;
        }

        //  Estado según vista del usuario
        $cantidadParaEstado = in_array($user->role_id, [1, 2, 4])
            ? $entregadoGlobal
            : $entregadoSede;

        $estado = 'Pendiente';

        if ($cantidadParaEstado >= $detalle->cantidad_solicitada) {
            $estado = $cantidadParaEstado > $detalle->cantidad_solicitada
                ? 'Con entrega extra'
                : 'Completo';
        }

        return [
            'id' => $detalle->id,
            'item' => $detalle->item,
            'descripcion' => $detalle->descripcion,
            'cantidad_solicitada' => (float) $detalle->cantidad_solicitada,

            //  ahora 100% consistentes
            'cantidad_entregada' => $entregadoGlobal,
            'cantidad_entregada_sede' => $entregadoSede,

            'estado_producto' => $estado,
            'updated_at' => $detalle->updated_at,
            'proveedor_id' => $detalle->proveedor_id,
            'proveedor_nombre' => $detalle->proveedor?->nombre,
            'proceso_bolsas_id' => $detalle->proceso_bolsas_id,
            'proceso_bolsas_nombre' => $detalle->procesoBolsas?->nombre,
            'producto_id' => $detalle->producto_id,
            'producto_nombre' => $detalle->producto?->nombre,
            'code' => $detalle->code,
            'origenes' => $detalle->origenes->map(function ($origen) {
                $cantidadPrioridad = (float) ($origen->cantidad_prioridad ?: $origen->cantidad_solicitada);
                $cantidadRecibida = (float) $origen->cantidad_recibida_aplicada;

                return [
                    'id' => $origen->id,
                    'orden_compra_id' => $origen->orden_compra_id,
                    'orden_compra_detalle_id' => $origen->orden_compra_detalle_id,
                    'producto_id' => $origen->producto_id,
                    'cantidad_solicitada' => (float) $origen->cantidad_solicitada,
                    'cantidad_prioridad' => $cantidadPrioridad,
                    'cantidad_recibida_aplicada' => $cantidadRecibida,
                    'prioridad_pendiente' => max($cantidadPrioridad - $cantidadRecibida, 0),
                    'prioridad_completa' => $cantidadRecibida >= $cantidadPrioridad,
                    'prioridad_snapshot' => $origen->prioridad_snapshot,
                ];
            })->values(),

            'entregas' => $detalle->entregas->map(function ($entrega) {
                return [
                    'id' => $entrega->id,
                    'cantidad_entregada' => (float) $entrega->cantidad_entregada,
                    'fecha_entrega' => optional($entrega->fecha_entrega)->format('Y-m-d H:i:s'),
                    'observaciones' => $entrega->observaciones,
                    'proveedor_id' => $entrega->proveedor_id,
                    'bodega_id' => $entrega->bodega_id,
                    'bodega_nombre' => $entrega->bodega?->nombre,
                    'product_id' => $entrega->product_id,
                    'product_nombre' => $entrega->product?->nombre,
                    'sede_id' => $entrega->sede_id,
                    'sede_nombre' => $entrega->bodega?->sede?->nombre ?? 'Sin sede',
                    'usuario_id' => $entrega->usuario_id,
                    'usuario_nombre' => $entrega->usuario?->name ?? 'Sin usuario',
                    'created_at' => optional($entrega->created_at)->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    });

    return response()->json([
        'id' => $orden->id,
        'numero_orden' => $orden->numero_orden,
        'sede_id' => $orden->sede_id,
        'sede_nombre' => $orden->sede?->nombre,
        'fecha' => $orden->fecha,
        'empresa' => $orden->empresa,
        'observaciones' => $orden->observaciones,
        'estado_registrado' => $orden->estado->nombre,
        'estado_calculado' => null,
        'proveedor_id' => $orden->proveedor_id,
        'proveedor_nombre' => $orden->proveedor->nombre ?? null,
        'usuario' => $orden->usuario->name ?? null,
        'productos' => $detalles,
    ]);
}

    /**
     * Update the specified resource in storage.
     */


public function entregasShow($id)
{
    $user = auth()->user();
    
    $orden = OrdenCompraProveedor::with([
        'proveedor', 
        'usuario', 
        'estado', 
        'detalles.entregas' => function ($query) use ($user) {
            // ✅ Filtrar entregas por sede del usuario si no es admin
            if (!in_array($user->role_id, [1, 2, 4])) {
                $query->where('sede_id', $user->sede_id);
            }
            // Los admins ven todas las entregas
            $query->with(['usuario:id,name', 'bodega:id,nombre,sede_id', 'bodega.sede:id,nombre']);
        }, 
        'detalles.procesoBolsas', 
        'detalles.proveedor',
        'empresa',
        'detalles.producto',
        'sede'
    ])->findOrFail($id);

    $detalles = $orden->detalles->map(function ($detalle) use ($user) {
        // ✅ Calcular cantidades según entregas de la sede del usuario
        $entregasSede = $detalle->entregas;
        
        // Si no es admin, solo contar entregas de su sede
        if (!in_array($user->role_id, [1, 2, 4])) {
            $cantidadEntregadaSede = $entregasSede->sum('cantidad_entregada');
        } else {
            // Para admins, mostrar total general y por sede
            $cantidadEntregadaSede = $entregasSede->where('sede_id', $user->sede_id)->sum('cantidad_entregada');
        }

        $estado = 'Pendiente';

        //  Estado basado en entregas de la sede del usuario (o todas si es admin)
        $cantidadParaEstado = in_array($user->role_id, [1, 2, 4]) 
            ? $detalle->cantidad_entregada  // Admins ven estado global
            : $cantidadEntregadaSede;       // Usuarios ven estado de su sede

        if ($cantidadParaEstado >= $detalle->cantidad_solicitada) {
            $estado = $cantidadParaEstado > $detalle->cantidad_solicitada
                ? 'Con entrega extra'
                : 'Completo';
        }

        return [
            'id' => $detalle->id,
            'item' => $detalle->item,
            'descripcion' => $detalle->descripcion,
            'cantidad_solicitada' => (float) $detalle->cantidad_solicitada,
            'cantidad_entregada' => (float) $detalle->cantidad_entregada, // Total general
            'cantidad_entregada_sede' => (float) $cantidadEntregadaSede, // ✅ Por sede del usuario
            'estado_producto' => $estado,
            'updated_at' => $detalle->updated_at,
            'proveedor_id' => $detalle->proveedor_id,
            'proveedor_nombre' => $detalle->proveedor?->nombre,
            'proceso_bolsas_id' => $detalle->proceso_bolsas_id,
            'proceso_bolsas_nombre' => $detalle->procesoBolsas?->nombre,
            'producto_id' => $detalle->producto_id,
            'producto_nombre' => $detalle->producto?->nombre,
            'code' => $detalle->code,

            //  Entregas filtradas por sede
            'entregas' => $detalle->entregas->map(function ($entrega) {
                return [
                    'id' => $entrega->id,
                    'cantidad_entregada' => (float) $entrega->cantidad_entregada,
                    'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                    'observaciones' => $entrega->observaciones,
                    'proveedor_id' => $entrega->proveedor_id,
                    'bodega_id' => $entrega->bodega_id,
                    'bodega_nombre' => $entrega->bodega?->nombre,
                    'product_id' => $entrega->product_id,
                    'product_nombre' => $entrega->product?->nombre,
                    
                    //  NUEVOS: Información de sede y usuario
                    'sede_id' => $entrega->sede_id,
                    'sede_nombre' => $entrega->bodega?->sede?->nombre ?? 'Sin sede',
                    'usuario_id' => $entrega->usuario_id,
                    'usuario_nombre' => $entrega->usuario?->name ?? 'Sin usuario',
                    'created_at' => $entrega->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    });

    // ✅ Calcular totales basados en la vista del usuario
    $total = $detalles->count();
    $completados = $detalles->where('estado_producto', 'Completo')->count();
    $conExtra = $detalles->where('estado_producto', 'Con entrega extra')->count();
    
    $estado_orden = match (true) {
        ($completados + $conExtra) === 0 => 'Pendiente',
        ($completados + $conExtra) < $total => 'Parcialmente Entregada',
        default => 'Completada',
    };

    // ✅ Estadísticas adicionales por sede
    $estadisticasSede = [
        'total_items' => $total,
        'items_completos' => $completados,
        'items_con_extra' => $conExtra,
        'items_pendientes' => $total - ($completados + $conExtra),
        'porcentaje_completado' => $total > 0 ? round((($completados + $conExtra) / $total) * 100, 2) : 0,
        
        // ✅ Información de la sede del usuario
        'sede_usuario' => [
            'id' => $user->sede_id,
            'nombre' => $user->sede->nombre ?? 'Sin sede asignada'
        ],
        
        // ✅ Totales de entregas por sede
        'total_entregado_todas_sedes' => $detalles->sum('cantidad_entregada'),
        'total_entregado_mi_sede' => $detalles->sum('cantidad_entregada_sede'),
    ];

    return response()->json([
        'id' => $orden->id,
        'numero_orden' => $orden->numero_orden,
        'sede_id' => $orden->sede_id,
        'sede_nombre' => $orden->sede?->nombre,
        'fecha' => $orden->fecha,
        'empresa' => $orden->empresa,
        'observaciones' => $orden->observaciones,
        'estado_registrado' => $orden->estado->nombre,
        'estado_calculado' => $estado_orden,
        'proveedor_id' => $orden->proveedor_id,
        'proveedor_nombre' => $orden->proveedor->nombre ?? null,
        'usuario' => $orden->usuario->name ?? null,
        'productos' => $detalles,
        
        // Información adicional del contexto del usuario
        'estadisticas_sede' => $estadisticasSede,
        'usuario_actual' => [
            'id' => $user->id,
            'nombre' => $user->name,
            'sede_id' => $user->sede_id,
            'sede_nombre' => $user->sede->nombre ?? null,
            'es_admin' => in_array($user->role_id, [1, 2, 4]),
        ],
    ]);
}


    public function updateProveedor(Request $request, $id)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id'
        ]);

        $orden = OrdenCompraProveedor::findOrFail($id);
        $orden->proveedor_id = $request->proveedor_id;
        $orden->save();

        return response()->json(['message' => 'Proveedor actualizado correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function storeDetalle(Request $request)
    {

        $request->validate([
            'orden_id' => 'required|exists:orden_compra_proveedores,id',
            'descripcion' => 'required|string|max:255',
            'cantidad_solicitada' => 'required|numeric|min:0',
            'item' => 'nullable|integer',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'proceso_bolsas_id' => 'nullable|exists:proceso_bolsas,id',
            'origenes' => 'nullable|array',
            'origenes.*.orden_compra_id' => 'required_with:origenes|exists:orden__compras,id',
            'origenes.*.orden_compra_detalle_id' => 'required_with:origenes|exists:orden__compra__detalles,id',
            'origenes.*.producto_id' => 'nullable|exists:products,id',
            'origenes.*.sede_id' => 'nullable|exists:sedes,id',
            'origenes.*.bodega_id' => 'nullable|exists:bodegas,id',
            'origenes.*.cantidad_solicitada' => 'required_with:origenes|numeric|min:0.01',
            'origenes.*.cantidad_prioridad' => 'nullable|numeric|min:0',
            'origenes.*.cantidad_recibida_aplicada' => 'nullable|numeric|min:0',
            'origenes.*.prioridad_snapshot' => 'nullable|array',

        ]);

        $nextItem = OrdenCompraProveedorDetalle::where('orden_id', $request->orden_id)->max('item') ?? 0;

        $detalle = new OrdenCompraProveedorDetalle();
        $detalle->orden_id = $request->orden_id;
        $detalle->descripcion = $request->descripcion;
        $detalle->cantidad_solicitada = $request->cantidad_solicitada;
        $detalle->item = $request->item ?? ($nextItem + 1);
        $detalle->proveedor_id = $request->proveedor_id ?? null;
        $detalle->proceso_bolsas_id = $request->proceso_bolsas_id ?? null;

        $detalle->save();

        $this->crearOrigenesDetalleProveedor(
            $detalle,
            $request->input('origenes', []),
            $detalle->orden
        );

        return response()->json(['message' => 'Detalle creado correctamente.']);
    }

    private function crearOrigenesDetalleProveedor(
        OrdenCompraProveedorDetalle $detalleProveedor,
        array $origenes,
        ?OrdenCompraProveedor $ordenProveedor = null
    ): void {
        foreach ($origenes as $origen) {
            if (empty($origen['orden_compra_id']) || empty($origen['orden_compra_detalle_id'])) {
                continue;
            }

            OrdenCompraProveedorDetalleOrigen::create([
                'orden_compra_proveedor_detalle_id' => $detalleProveedor->id,
                'orden_compra_id' => $origen['orden_compra_id'],
                'orden_compra_detalle_id' => $origen['orden_compra_detalle_id'],
                'producto_id' => $origen['producto_id'] ?? $detalleProveedor->producto_id,
                'sede_id' => $origen['sede_id'] ?? $ordenProveedor?->sede_id,
                'bodega_id' => $origen['bodega_id'] ?? $ordenProveedor?->bodega_id,
                'cantidad_solicitada' => $origen['cantidad_solicitada'] ?? $detalleProveedor->cantidad_solicitada,
                'cantidad_prioridad' => $origen['cantidad_prioridad']
                    ?? $origen['cantidad_solicitada']
                    ?? $detalleProveedor->cantidad_solicitada,
                'cantidad_recibida_aplicada' => $origen['cantidad_recibida_aplicada'] ?? 0,
                'prioridad_snapshot' => $origen['prioridad_snapshot'] ?? null,
            ]);
        }
    }

   public function update(UpdateOrdenProveedorRequest $request, $id)
{
    $user = auth()->user();

    $orden = OrdenCompraProveedor::with('detalles.entregas')
        ->findOrFail($id);

    DB::beginTransaction();

    try {

        //  Actualizar cabecera
        $orden->update([
            'observaciones' => $request->observaciones,
            'sede_id' => $orden->sede_id ?? $user->sede_id,
            'empresa_id' => $request->empresa_id,
            'proveedor_id' => $request->proveedor_id,
        ]);

 

        foreach ($request->detalles as $detalleRequest) {

            $tieneId = !empty($detalleRequest['id']);

            $detalle = $tieneId
                ? $orden->detalles()->with('entregas')->where('id', $detalleRequest['id'])->first()
                : null;

        

            if ($tieneId && $detalle) {

                if ($detalle->entregas->count() > 0) {

                    $detalle->update([
                        'descripcion' => $detalleRequest['descripcion'] ?? $detalle->descripcion,
                    ]);

                } else {

                    $detalle->update([
                        'descripcion'         => $detalleRequest['descripcion'] ?? null,
                        'cantidad_solicitada' => $detalleRequest['cantidad_solicitada'],
                        'code'                => $detalleRequest['code'] ?? null,
                        'producto_id'         => $detalleRequest['producto_id'],
                        'proveedor_id'        => $detalleRequest['proveedor_id'] ?? null,
                        'proceso_bolsas_id'   => $detalleRequest['proceso_bolsas_id'] ?? null,
                    ]);
                }

            } elseif (!$tieneId) {

                $orden->detalles()->create([
                    'item'                => $detalleRequest['item'] ?? 1,
                    'descripcion'         => $detalleRequest['descripcion'] ?? null,
                    'cantidad_solicitada' => $detalleRequest['cantidad_solicitada'],
                    'cantidad_entregada'  => 0,
                    'code'                => $detalleRequest['code'] ?? null,
                    'producto_id'         => $detalleRequest['producto_id'],
                    'proveedor_id'        => $detalleRequest['proveedor_id'] ?? null,
                    'proceso_bolsas_id'   => $detalleRequest['proceso_bolsas_id'] ?? null,
                ]);
            }
            // Si vino id pero no matchea en esta orden → se ignora
        }

        DB::commit();

        return response()->json([
            'message' => 'Orden actualizada correctamente.'
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'error' => 'Error al actualizar la orden',
            'detalle' => $e->getMessage()
        ], 500);
    }
}



private function ordenTieneEntregas(OrdenCompraProveedor $orden): bool
{
    return $orden->detalles()
        ->whereHas('entregas')
        ->exists();
}

    //Eliminar un detalle de orden de compra
public function destroy($id)
{
    $user = auth()->user();

    // Roles permitidos
    $rolesPermitidos = [1, 4];

    if (!in_array($user->role_id, $rolesPermitidos)) {
        return response()->json([
            'success' => false,
            'message' => 'No tiene permisos para eliminar este detalle.'
        ], 403);
    }

    $detalle = OrdenCompraProveedor::findOrFail($id);
    $detalle->delete();

    return response()->json([
        'success' => true,
        'message' => 'Detalle eliminado correctamente.'
    ]);
}


    //Descargar la orden de compra del proveedor en pdf
    public function descargarOrdenPdfProveedor($id)
    {
        $orden = OrdenCompraProveedor::with(['proveedor', 
        'empresa',
         'usuario',
         'estado',
         'detalles.entregas',
         'detalles.procesoBolsas',
         'detalles.proveedor'])->findOrFail($id);


  // Convertir logo a base64 si existe
    if ($orden->empresa && $orden->empresa->logo) {
        $logoPath = storage_path('app/public/' . $orden->empresa->logo);
        
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoMimeType = mime_content_type($logoPath);
            $orden->empresa->logo_base64 = "data:{$logoMimeType};base64,{$logoData}";
        }
    }


        $pdf = Pdf::loadView('pdf.orden_compra_proveedor', compact('orden'));
        return $pdf->download('orden_compra_proveedor_' . $orden->numero_orden . '.pdf');
    }   
    

    //Enviar la orden de compra por correo electrónico al proveedor
    public function enviarEmail($id)
    {
        $orden = OrdenCompraProveedor::with(['proveedor', 'empresa', 'usuario', 'estado', 'detalles.entregas', 'detalles.procesoBolsas', 'detalles.proveedor'])->findOrFail($id);

        // Generar el PDF
        $pdf = Pdf::loadView('pdf.orden_compra_proveedor', compact('orden'))->output();

        // Enviar el correo
        Mail::to($orden->proveedor->correo)->send(new OrdenCompraProveedorMail($orden, $pdf));

        return response()->json(['message' => 'Correo enviado correctamente al proveedor.']);
    }

public function dividirOrden(Request $request, $id)
{
    $request->validate([
        'items' => 'required|array|min:1',
        'items.*.detalle_id' => 'required|exists:orden_compra_proveedor_detalles,id',
        'items.*.proveedor_id' => 'required|exists:proveedores,id'
    ]);

    DB::beginTransaction();

    try {
        $ordenOriginal = OrdenCompraProveedor::with("detalles.observaciones")->findOrFail($id);

        // Agrupar por proveedor destino
        $grupos = collect($request->items)
            ->groupBy("proveedor_id"); // 🟢 Grupo = proveedor_id → lista de detalles

        $nuevasOrdenes = [];

        foreach ($grupos as $proveedorId => $itemsProveedor) {

            // Crear nueva orden para este proveedor
            $newOrden = OrdenCompraProveedor::create([
                'proveedor_id' => $proveedorId,
                'fecha' => now(),
                'numero_orden' => $this->generarConsecutivo(),
                'estado_id' => 1,
                'usuario_id' => auth()->id(),
                'observaciones' => "Generada automáticamente desde división de la orden {$ordenOriginal->numero_orden}",
                'empresa_id' => $ordenOriginal->empresa_id,
                'bodega_id' => $ordenOriginal->bodega_id,
                'sede_id' => auth()->user()->sede_id,
            ]);

            // Asociar los detalles correspondientes
            foreach ($itemsProveedor as $item) {
                $detalle = OrdenCompraProveedorDetalle::with('observaciones')->findOrFail($item['detalle_id']);
       
                $nuevoDetalle = $newOrden->detalles()->create([
                    'item' => $detalle->item,
                    'descripcion' => $detalle->descripcion,
                    'cantidad_solicitada' => $detalle->cantidad_solicitada,
                    'cantidad_entregada' => 0,
                    'code' => $detalle->code,
                    'producto_id' => $detalle->producto_id,
                    'proveedor_id' => $proveedorId,
                    'proceso_bolsas_id' => $detalle->proceso_bolsas_id,
                ]);

                foreach ($detalle->observaciones as $observacion) {
                    OrdenDetalleObservaciones::create([
                        'orden_detalle_id' => $nuevoDetalle->id,
                        'proceso_bolsas_id' => $observacion->proceso_bolsas_id,
                        'proveedor_id' => $observacion->proveedor_id,
                        'observacion' => $observacion->observacion,
                        'estado' => 'pendiente',
                        'usuario_id' => $observacion->usuario_id ?? auth()->id(),
                    ]);
                }
            }// Cargar relaciones necesarias para PDF y frontend
$newOrden->load([
    'empresa',
    'proveedor',
    'usuario',
    'detalles.observaciones'
]);

$nuevasOrdenes[] = $newOrden;

        }


        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Órdenes generadas correctamente',
            'ordenes_generadas' => $nuevasOrdenes

        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error al dividir la orden',
            'error' => $e->getMessage()
        ], 500);
    }
}

private function generarConsecutivo()
{
    $ultima = OrdenCompraProveedor::orderBy('id', 'desc')->first();
    $num = $ultima ? $ultima->id + 1 : 1;
    return 'OC-' . str_pad($num, 3, '0', STR_PAD_LEFT);
}


}
