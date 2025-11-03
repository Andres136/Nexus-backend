<?php

namespace App\Services\Crm;

use App\Models\Crm\bodega;
use App\Models\Crm\Inventario;
use App\Models\Crm\MovimientoStock;
use App\Models\Crm\Sede;
use App\Models\Traslados\Detalles_envio_internos;
use App\Models\Traslados\Envio_internos;
use App\Models\Traslados\Envio_orden;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InventarioService
{
   /**
 * Obtener inventarios con filtros, estadísticas y totales
 */
public function listarInventarios(Request $request, $user)
{
    try {
        $rolesPermitidos = [1, 2]; // SuperAdmin y Admin
        $isAdmin = in_array($user->role_id, $rolesPermitidos);

        // ===============================
        // 🔹 QUERY BASE CON RELACIONES
        // ===============================
        $query = Inventario::with([
            'producto:id,name,code',
            'empresa:id,nombre',
            'sede:id,nombre',
            'bodega:id,nombre'
        ])->select('inventories.*');

        // ===============================
        // 🔹 CONTROL DE ACCESO POR ROL
        // ===============================
        if (!$isAdmin) {
            if ($user->sede_id) {
                $query->where('inventories.sede_id', $user->sede_id);
            }

        }

        // ===============================
        // 🔹 FILTROS DINÁMICOS
        // ===============================
        if ($request->filled('empresa_id')) {
            // ✅ empresa opcional: si viene, filtra; si no, muestra todo
            $query->where('inventories.empresa_id', $request->empresa_id);
        }

        if ($request->filled('sede_id')) {
            $query->where('inventories.sede_id', $request->sede_id);
        }

        if ($request->filled('bodega_id')) {
            $query->where('inventories.bodega_id', $request->bodega_id);
        }

        if ($request->filled('producto')) {
            $producto = $request->producto;
            $query->whereHas('producto', function ($q) use ($producto) {
                $q->where('name', 'LIKE', "%{$producto}%")
                  ->orWhere('code', 'LIKE', "%{$producto}%");
            });
        }

        if ($request->boolean('stock_bajo')) {
            $query->whereColumn('stock', '<', 'min_stock');
        }

        if ($request->filled('stock_min')) {
            $query->where('stock', '>=', $request->stock_min);
        }

        if ($request->filled('stock_max')) {
            $query->where('stock', '<=', $request->stock_max);
        }

        if ($request->filled('ultimo_movimiento_dias')) {
            $fechaLimite = now()->subDays($request->ultimo_movimiento_dias);
            $query->where('updated_at', '>=', $fechaLimite);
        }

        if ($request->filled('vencimiento_dias')) {
            $fechaLimite = now()->addDays($request->vencimiento_dias);
            $query->whereNotNull('fecha_vencimiento')
                  ->where('fecha_vencimiento', '<=', $fechaLimite);
        }

        // ===============================
        // 🔹 ORDENAMIENTO SEGURO
        // ===============================
        $orderBy = $request->get('order_by', 'updated_at');
        $orderDirection = $request->get('order_direction', 'desc');
        $allowedOrderBy = ['stock', 'precio', 'updated_at', 'created_at', 'fecha_vencimiento'];

        if (in_array($orderBy, $allowedOrderBy)) {
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        // ===============================
        // 🔹 PAGINACIÓN
        // ===============================
        $perPage = $request->get('per_page', 20);
        $inventarios = $query->paginate($perPage);

        // ===============================
        // 🔹 TOTALES GLOBALES
        // ===============================
        $totalQuery = clone $query;
        $totales = [
            'total_productos' => $totalQuery->distinct('producto_id')->count('producto_id'),
            'total_stock'     => $totalQuery->sum('stock'),
            'valor_total'     => $totalQuery->sum(DB::raw('stock * precio')),
        ];

        // ===============================
        // 🔹 ESTADÍSTICAS GENERALES
        // ===============================
        $statsQuery = clone $query;
        $estadisticas = [
            'total_productos' => $statsQuery->distinct('producto_id')->count('producto_id'),
            'total_stock'     => $statsQuery->sum('stock'),
            'stock_bajo'      => (clone $statsQuery)->whereColumn('stock', '<', 'min_stock')->count(),
            'valor_total'     => $statsQuery->sum(DB::raw('stock * precio')),
            'productos_vencidos' => (clone $statsQuery)->whereNotNull('fecha_vencimiento')
                                                       ->where('fecha_vencimiento', '<', now())->count(),
            'productos_por_vencer' => (clone $statsQuery)->whereNotNull('fecha_vencimiento')
                                                        ->whereBetween('fecha_vencimiento', [now(), now()->addDays(30)])
                                                        ->count(),
        ];

        // ===============================
        // 🔹 ESTADÍSTICAS POR FILTRO
        // ===============================
        $estadisticasPorFiltro = [];

        if ($isAdmin) {
            $estadisticasPorFiltro['por_sede'] = Inventario::select('sede_id')
                ->selectRaw('COUNT(*) as total_productos, SUM(stock) as total_stock, SUM(stock * precio) as valor_total')
                ->with('sede:id,nombre')
                ->groupBy('sede_id')
                ->get();
        }

        $estadisticasPorFiltro['por_bodega'] = (clone $query)
            ->select('bodega_id')
            ->selectRaw('COUNT(*) as total_productos, SUM(stock) as total_stock, SUM(stock * precio) as valor_total')
            ->with('bodega:id,nombre')
            ->groupBy('bodega_id')
            ->get();

        $estadisticasPorFiltro['por_empresa'] = (clone $query)
            ->select('empresa_id')
            ->selectRaw('COUNT(*) as total_productos, SUM(stock) as total_stock, SUM(stock * precio) as valor_total')
            ->with('empresa:id,nombre')
            ->groupBy('empresa_id')
            ->get();

        // ===============================
        // 🔹 ÚLTIMOS MOVIMIENTOS
        // ===============================
        $movimientos = MovimientoStock::with(['producto:id,name,code', 'usuario:id,name'])
            ->when(!$isAdmin, function ($q) use ($user) {
                $q->whereHas('producto.inventarios', fn($sub) => $sub->where('sede_id', $user->sede_id));
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // ===============================
        // 🔹 CATÁLOGOS DE FILTRO (TODAS LAS SEDES, INCLUSO SIN INVENTARIO)
        // ===============================
        $sedesDisponibles = Sede::all(['id', 'nombre']);
        $bodegasDisponibles = bodega::all(['id', 'nombre']);

        // ===============================
        // 🔹 CATÁLOGOS DE FILTRO (CORREGIDO)
        // ===============================

if (in_array($user->role_id, $rolesPermitidos)) {
    // 🟢 ADMINS: TODAS las sedes del sistema (no solo las que tienen inventario)
    $sedesDisponibles = Sede::all(['id', 'nombre']); // ⚡ CAMBIO AQUÍ
    
    // Para bodegas también todas las del sistema
       $bodegasDisponibles = bodega::with('sede:id,nombre')
                ->select(['id', 'nombre', 'sede_id']) // ✅ INCLUIR sede_id
                ->get()
                ->map(function ($bodega) {
                    return [
                        'id' => $bodega->id,
                        'nombre' => $bodega->nombre,
                        'sede_id' => $bodega->sede_id, // ✅ INCLUIR sede_id
                        'sede' => $bodega->sede ? [
                            'id' => $bodega->sede->id,
                            'nombre' => $bodega->sede->nombre
                        ] : null
                    ];
                }); // ⚡ CAMBIO AQUÍ

    Log::info('Admin - TODAS las sedes del sistema:', [
        'user_id' => $user->id,
        'role_id' => $user->role_id,
        'sedes_count' => $sedesDisponibles->count(),
        'bodegas_count' => $bodegasDisponibles->count(),
        'sedes_nombres' => $sedesDisponibles->pluck('nombre')->toArray(),
        'bodegas_nombres' => $bodegasDisponibles->pluck('nombre')->toArray()
    ]);
} else {
    // 🟢 USUARIOS NORMALES: Solo su sede y bodegas de su sede
    $sedesDisponibles = Sede::where('id', $user->sede_id)->get(['id', 'nombre']);
 $bodegasDisponibles = bodega::with('sede:id,nombre')
                ->select(['id', 'nombre', 'sede_id']) 
    ->whereHas('inventarios', function($q) use ($user) {
        $q->where('sede_id', $user->sede_id);
    })->get(['id', 'nombre']);

    Log::info('Usuario normal - Solo su sede:', [
        'user_id' => $user->id,
        'user_sede_id' => $user->sede_id,
        'sedes_count' => $sedesDisponibles->count(),
        'bodegas_count' => $bodegasDisponibles->count(),
        'sedes_nombres' => $sedesDisponibles->pluck('nombre')->toArray(),
        'bodegas_nombres' => $bodegasDisponibles->pluck('nombre')->toArray()
    ]);
}
        // ===============================
        // 🔹 RESPUESTA FINAL
        // ===============================
        return [
            'inventarios' => $inventarios,
            'totales' => $totales,
            'estadisticas' => $estadisticas,
            'estadisticas_por_filtro' => $estadisticasPorFiltro,
            'ultimos_movimientos' => $movimientos,
            'sedes' => $sedesDisponibles,
            'bodegas' => $bodegasDisponibles,
        ];

    } catch (\Throwable $e) {
        return [
            'error' => true,
            'message' => 'Error al obtener inventarios',
            'detalle' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null
        ];
    }
}

/**
 * Registrar Traslado de inventario entre sedes 
 * 
 */


public function registrarTraslado($data, $user)
{
    DB::beginTransaction();
    try {
        $origen = Inventario::where('producto_id', $data['producto_id'])
            ->where('sede_id', $data['sede_origen_id'])
            ->where('bodega_id', $data['bodega_origen_id'])
            ->lockForUpdate()
            ->first();

        if (!$origen) {
            throw new \Exception('Inventario de origen no encontrado');
        }

        // Verificar si hay suficiente stock en la bodega de origen
        if ($origen->cantidad < $data['cantidad']) {
            throw new \Exception('Stock insuficiente en la bodega de origen');
        }

        // Registrar el movimiento de traslado
        $movimiento = new MovimientoStock();
        $movimiento->producto_id = $data['product_id'];
        $movimiento->sede_id = $data['sede_origen_id'];
        $movimiento->bodega_id = $data['bodega_origen_id'];
        $movimiento->cantidad = $data['cantidad'];
        $movimiento->tipo = 'traslado';
        $movimiento->usuario_id = $user->id;
        $movimiento->save();

        // Actualizar el inventario de origen
        $origen->cantidad -= $data['cantidad'];
        $origen->save();

        // Registrar el inventario de destino
        $destino = Inventario::where('producto_id', $data['producto_id'])
            ->where('sede_id', $data['sede_destino_id'])
            ->where('bodega_id', $data['bodega_destino_id'])
            ->first();

        if ($destino) {
            // Si existe, solo actualizamos la cantidad
            $destino->cantidad += $data['cantidad'];
            $destino->save();
        } else {
            // Si no existe, creamos un nuevo registro
            $destino = new Inventario();
            $destino->producto_id = $data['producto_id'];
            $destino->sede_id = $data['sede_destino_id'];
            $destino->bodega_id = $data['bodega_destino_id'];
            $destino->cantidad = $data['cantidad'];
            $destino->save();
        }

        DB::commit();
        return [
            'error' => false,
            'message' => 'Traslado registrado con éxito',
            'movimiento_id' => $movimiento->id
        ];
    } catch (\Throwable $e) {
        DB::rollBack();
        return [
            'error' => true,
            'message' => 'Error al registrar traslado',
            'detalle' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null
        ];
    }

}
public function registrarEnvioConDescuento($data, $user)
{
    DB::beginTransaction();

    try {
        // 🟢 1) Crear envío principal
        $sedeOrigen = $user->sede_id;
        $envio = Envio_internos::create([
            'sede_origen_id'  => $sedeOrigen,
            'sede_destino_id' => $data['sede_destino_id'],
            'empresa_id'      => $data['empresa_id'] ?? null,
            'usuario_id'      => $user->id,
            'estado_id'       => $data['estado_id'] ?? 1,
            'fecha_envio'     => now(),
            'notas'           => $data['notas'] ?? null,
        ]);

        $detallesRegistrados = [];
        $movimientos = []; // registros por inventario consumido (para auditoría)

        // 🟢 2) Registrar detalles (una o varias bodegas por producto)
        foreach ($data['detalles'] as $detalle) {
            $productoId = $detalle['product_id'];

            // Soporta múltiples bodegas seleccionadas por el front
            $bodegas = $detalle['bodegas'] ?? [[
                'bodega_id' => $detalle['bodega_origen_id'] ?? null,
                'cantidad'  => $detalle['cantidad'] ?? 0,
            ]];

            foreach ($bodegas as $bodega) {
                $bodegaId = (int) ($bodega['bodega_id'] ?? 0);
                $cantidad = (float) ($bodega['cantidad'] ?? 0);

                if ($bodegaId <= 0) {
                    throw new \Exception("Bodega no especificada en el detalle del producto {$productoId}.");
                }
                if ($cantidad <= 0) {
                    throw new \Exception("La cantidad a trasladar debe ser mayor que 0 para el producto {$productoId}.");
                }

                // 🔒 Traer TODOS los inventarios de esa bodega (pueden ser varias empresas) y bloquear
                $inventariosOrigen = Inventario::where('producto_id', $productoId)
                    ->where('sede_id', $sedeOrigen)
                    ->where('bodega_id', $bodegaId)
                    ->lockForUpdate()
                    ->orderBy('stock', 'DESC') // primero los que más stock tienen
                    ->get();

                if ($inventariosOrigen->isEmpty()) {
                    throw new \Exception("Inventario no encontrado para el producto {$productoId} en la bodega {$bodegaId}.");
                }

                // ✅ Validación consolidada
                $stockTotal = (float) $inventariosOrigen->sum('stock');
                if ($stockTotal < $cantidad) {
                    throw new \Exception("Stock insuficiente en bodega {$bodegaId} ({$stockTotal} disponibles).");
                }

                // ✅ Descontar Opción A: del primero que pueda cubrir, sino ir agotando
                $restante = $cantidad;
                $consumos = []; // [ ['inventario_id'=>x, 'consumido'=>y], ... ]

                foreach ($inventariosOrigen as $inv) {
                    if ($restante <= 0) break;

                    $disponible = (float) $inv->stock;
                    if ($disponible <= 0) {
                        continue;
                    }

                    if ($disponible >= $restante) {
                        // este inventario cubre todo lo que falta
                        $inv->stock = $disponible - $restante;
                        $inv->save();

                        $consumos[] = ['inventario_id' => $inv->id, 'consumido' => $restante];
                        $restante = 0;
                    } else {
                        // agotar este inventario y seguir
                        $inv->stock = 0;
                        $inv->save();

                        $consumos[] = ['inventario_id' => $inv->id, 'consumido' => $disponible];
                        $restante -= $disponible;
                    }
                }

                // 🟢 Registrar detalle del envío (por bodega)
                $detalleEnvio = Detalles_envio_internos::create([
                    'envio_interno_id' => $envio->id,
                    'item'             => $detalle['item'] ?? null,
                    'product_id'       => $productoId,
                    'code_id'          => $detalle['code_id'] ?? null,
                    'descripcion'      => $detalle['descripcion'] ?? ($detalle['novedades'] ?? null),
                    'cantidad'         => $cantidad,
                    'bodega_origen_id' => $bodegaId,
                    'orden_compra_id'  => $detalle['orden_compra_id'] ?? null,
                ]);

                $detallesRegistrados[] = $detalleEnvio;

                // 🟢 Movimientos por cada consumo real de inventario
                foreach ($consumos as $c) {
                    $movimientos[] = [
                        'producto_id'     => $productoId,
                        'cantidad'        => $c['consumido'],
                        'inventario_id'   => $c['inventario_id'],
                        'bodega_origen_id'=> $bodegaId,
                        'tipo'            => 'envio_interno',
                        'usuario_id'      => $user->id,
                        'detalle'         => "Envío interno #{$envio->id} desde bodega {$bodegaId} hacia sede {$data['sede_destino_id']}",
                    ];
                }
            }
        }

        // 🟢 3) Registrar movimiento consolidado (resumen)
        MovimientoStock::create([
            'tipo'            => 'traslado_multiple',
            'usuario_id'      => $user->id,
            'sede_origen_id'  => $sedeOrigen,
            'sede_destino_id' => $data['sede_destino_id'],
            'cantidad'        => collect($movimientos)->sum('cantidad'),
            'detalle'         => json_encode([
                'envio_id' => $envio->id,
                'detalles' => $movimientos,
                'fecha'    => now()->toDateTimeString(),
                'observacion' => $data['notas'] ?? 'Traslado interno entre sedes',
            ]),
        ]);

        // 🟢 4) Asociar órdenes (se eliminó el bloque duplicado)
        if (!empty($data['ordenes_compra'])) {
            foreach ($data['ordenes_compra'] as $ordenId) {
                Envio_orden::create([
                    'envio_interno_id' => $envio->id,
                    'orden_compra_id'  => $ordenId,
                ]);
            }
        }

        // 🟢 5) Generar PDF y guardar ruta
        $pdfPath = $this->generarPdfEnvio($envio, $detallesRegistrados);

        DB::commit();

        return [
            'success'  => true,
            'message'  => 'Envío registrado, stock descontado y órdenes vinculadas correctamente.',
            'envio'    => $envio,
            'detalles' => $detallesRegistrados,
            'pdf'      => asset('storage/'.$pdfPath),
        ];
    } catch (\Throwable $e) {
        DB::rollBack();
        return [
            'success' => false,
            'message' => 'Error al registrar el envío interno.',
            'error'   => $e->getMessage(),
        ];
    }
}

private function generarPdfEnvio($envio, $detalles) 
{
    $pdf = Pdf::loadView('pdf.envio-interno', [
        'envio' => $envio,
        'detalles' => $detalles
    ])->setPaper('A4', 'portrait');

    $fileName = 'envio_interno_' . $envio->id . '.pdf';
    $filePath = 'envios/' . $fileName;

    Storage::disk('public')->put($filePath, $pdf->output());

    return $filePath;
}


public function descontarStockMasivo(array $items, $user)
{
    $resultados = [];
    $erroresGlobales = [];

    DB::beginTransaction();
    try {
        foreach ($items as $item) {
            $productoId      = (int) $item['producto_id'];
            $cantidadTotal   = (float) $item['cantidad'];
            $bodegas         = $item['bodegas'] ?? [];
            $equivalentes    = $item['producto_equivalentes'] ?? [];
            $ordenTrabajoId  = $item['orden_trabajo_id'] ?? null;
            $ordenCompraId   = $item['orden_compra_id'] ?? null;
            $sedeId          = $user->sede_id;

            $cantidadCubierta = 0;
            $detalleOriginal  = [];
            $equivalentesResp = [];
            $errores          = [];

            // 1. Descontar bodegas del producto original
            foreach ($bodegas as $bodega) {
                $bodegaId      = (int) $bodega['bodega_id'];
                $cantDescontar = (float) $bodega['cantidad'];

                $inventariosOrigen = \App\Models\Crm\Inventario::where('producto_id', $productoId)
                    ->where('sede_id', $sedeId)
                    ->where('bodega_id', $bodegaId)
                    ->lockForUpdate()
                    ->orderBy('stock', 'DESC')
                    ->get();

                if ($inventariosOrigen->isEmpty()) {
                    $errores[] = [
                        'producto_id' => $productoId,
                        'bodega_id'   => $bodegaId,
                        'mensaje'     => "No hay inventario disponible en la bodega seleccionada"
                    ];
                    continue;
                }

                $stockTotal = (float) $inventariosOrigen->sum('stock');
                if ($stockTotal < $cantDescontar) {
                    $errores[] = [
                        'producto_id' => $productoId,
                        'bodega_id'   => $bodegaId,
                        'mensaje'     => "Stock insuficiente: Disponible {$stockTotal}, Requerido {$cantDescontar}"
                    ];
                    continue;
                }

                $restante = $cantDescontar;
                foreach ($inventariosOrigen as $inv) {
                    if ($restante <= 0) break;
                    $disponible = (float) $inv->stock;
                    if ($disponible <= 0) continue;

                    if ($disponible >= $restante) {
                        $inv->decrement('stock', $restante);
                        $detalleOriginal[] = [
                            'producto_id'        => $productoId,
                            'bodega_id'          => $bodegaId,
                            'inventario_id'      => $inv->id,
                            'cantidad_descontada'=> $restante,
                            'stock_restante'     => $inv->stock
                        ];
                        $cantidadCubierta += $restante;
                        $restante = 0;
                    } else {
                        $inv->decrement('stock', $disponible);
                        $detalleOriginal[] = [
                            'producto_id'        => $productoId,
                            'bodega_id'          => $bodegaId,
                            'inventario_id'      => $inv->id,
                            'cantidad_descontada'=> $disponible,
                            'stock_restante'     => $inv->stock
                        ];
                        $cantidadCubierta += $disponible;
                        $restante -= $disponible;
                    }
                }
            }

            // 2. Descontar equivalentes si hay déficit
            $deficit = max(0, $cantidadTotal - $cantidadCubierta);
            if ($deficit > 0) {
                foreach ($equivalentes as $equivalente) {
                    $eqId   = (int) $equivalente['id'];
                    $razon  = $equivalente['razon'] ?? 'Equivalente por falta de stock';
                    $productoEq = \App\Models\Crm\product::find($eqId);

                    $eqResp = [
                        'producto_id'      => $eqId,
                        'producto_nombre'  => $productoEq?->name ?? "Producto #{$eqId}",
                        'razon'            => $razon,
                        'bodegas'          => []
                    ];

                    foreach ($equivalente['bodegas'] as $bodegaEq) {
                        $bodegaId  = (int) $bodegaEq['bodega_id'];
                        $cantEq    = (float) $bodegaEq['cantidad'];

                        $inventariosEq = \App\Models\Crm\Inventario::where('producto_id', $eqId)
                            ->where('sede_id', $sedeId)
                            ->where('bodega_id', $bodegaId)
                            ->lockForUpdate()
                            ->orderBy('stock', 'DESC')
                            ->get();

                        if ($inventariosEq->isEmpty()) {
                            $eqResp['bodegas'][] = [
                                'bodega_id' => $bodegaId,
                                'error'     => "No hay inventario disponible en esta bodega para el equivalente",
                            ];
                            continue;
                        }

                        $stockTotalEq = (float) $inventariosEq->sum('stock');
                        if ($stockTotalEq < $cantEq) {
                            $eqResp['bodegas'][] = [
                                'bodega_id' => $bodegaId,
                                'error'     => "Stock insuficiente en equivalente. Disponible {$stockTotalEq}, Requerido {$cantEq}"
                            ];
                            continue;
                        }

                        $restante = $cantEq;
                        foreach ($inventariosEq as $invEq) {
                            if ($restante <= 0) break;
                            $disponible = (float) $invEq->stock;
                            if ($disponible <= 0) continue;

                            if ($disponible >= $restante) {
                                $invEq->decrement('stock', $restante);
                                $eqResp['bodegas'][] = [
                                    'bodega_id'          => $bodegaId,
                                    'inventario_id'      => $invEq->id,
                                    'cantidad_descontada'=> $restante,
                                    'stock_restante'     => $invEq->stock,
                                ];
                                $cantidadCubierta += $restante;
                                $restante = 0;
                            } else {
                                $invEq->decrement('stock', $disponible);
                                $eqResp['bodegas'][] = [
                                    'bodega_id'          => $bodegaId,
                                    'inventario_id'      => $invEq->id,
                                    'cantidad_descontada'=> $disponible,
                                    'stock_restante'     => $invEq->stock,
                                ];
                                $cantidadCubierta += $disponible;
                                $restante -= $disponible;
                            }
                        }
                    }
                    $equivalentesResp[] = $eqResp;
                }
            }

            // 3. Consolidar movimiento global (puedes adaptar esto según tu lógica)
            $movimiento = \App\Models\Crm\MovimientoStock::firstOrCreate(
                [
                    'orden_trabajo_id' => $ordenTrabajoId,
                    'tipo'             => 'descuento_masivo',
                ],
                [
                    'orden_compra_id'  => $ordenCompraId,
                    'usuario_id'       => $user->id,
                    'cantidad'         => 0,
                    'detalle'          => [
                        'bodegas'      => [],
                        'equivalentes' => [],
                        'errores'      => [],
                    ],
                ]
            );

            $detalleActual = $movimiento->detalle ?? [
                'bodegas'      => [],
                'equivalentes' => [],
                'errores'      => [],
            ];

            $detalleActual['bodegas']      = array_merge($detalleActual['bodegas'], $detalleOriginal);
            $detalleActual['equivalentes'] = array_merge($detalleActual['equivalentes'], $equivalentesResp);
            $detalleActual['errores']      = array_merge($detalleActual['errores'], $errores);

            $movimiento->update([
                'detalle'  => $detalleActual,
                'cantidad' => $movimiento->cantidad + $cantidadTotal,
            ]);

            $faltante = max(0, $cantidadTotal - $cantidadCubierta);

            $resultados[] = [
                'producto_id'        => $productoId,
                'cantidad_requerida' => $cantidadTotal,
                'detalle_original'   => $detalleOriginal,
                'equivalentes'       => $equivalentesResp,
                'errores'            => $errores,
                'faltante'           => $faltante,
                'movimiento_global'  => [
                    'id'   => $movimiento->id,
                    'pdf'  => isset($movimiento->pdf_path) ? asset("storage/{$movimiento->pdf_path}") : null,
                ],
                'success'            => $faltante === 0,
                'message'            => $faltante === 0 
                    ? "Stock descontado exitosamente"
                    : "Faltan {$faltante} unidades por cubrir",
            ];
            if (!empty($errores)) {
                $erroresGlobales = array_merge($erroresGlobales, $errores);
            }
        }

        DB::commit();
        return [
            'success' => empty($erroresGlobales),
            'resultados' => $resultados,
            'errores' => $erroresGlobales,
        ];
    } catch (\Throwable $e) {
        DB::rollBack();
        return [
            'success' => false,
            'error'   => $e->getMessage(),
            'resultados' => $resultados,
            'errores' => $erroresGlobales,
        ];
    }
}



}