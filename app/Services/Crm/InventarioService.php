<?php

namespace App\Services\Crm;

use App\EstadoEnum;
use App\Models\Crm\bodega;
use App\Models\Crm\categoria;
use App\Models\Crm\Inventario;
use App\Models\Crm\MovimientoStock;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\Orden_Compra_Detalle;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Crm\PrestamoStockEmpresa;
use App\Models\Crm\product;
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
        $rolesPermitidos = [1,3,4,5,6,7,9,9,10,11]; // SuperAdmin y Admin
        $isAdmin = in_array($user->role_id, $rolesPermitidos);

        // ===============================
        // 🔹 QUERY BASE CON RELACIONES
        // ===============================
        $query = Inventario::with([
            'producto:id,name,code,categoria_id',
            'empresa:id,nombre',
            'sede:id,nombre',
            'bodega:id,nombre'
        ])->select('inventories.*');


        //=====================
        // 🔹 RELACION CON CATEGORIA




        // ======================
        if ($request->filled('filtro_estatico')) {
            $query->where('inventories.filtro_estatico', $request->filtro_estatico);
        }

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
        if ($request->filled('categoria_id')) {
    $query->whereHas('producto', function ($q) use ($request) {
        $q->where('categoria_id', $request->categoria_id);
    });
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
        ->orderBy('sede_id') // ✅ orden seguro permitido
        ->get();
}

// ✅ CORREGIDO: quitar orderBy heredado del clone $query
$estadisticasPorFiltro['por_bodega'] = Inventario::select('bodega_id')
    ->selectRaw('COUNT(*) as total_productos, SUM(stock) as total_stock, SUM(stock * precio) as valor_total')
    ->with('bodega:id,nombre')
    ->groupBy('bodega_id')
    ->orderBy('bodega_id') // ✅ agrega un orden válido opcional
    ->get();

$estadisticasPorFiltro['por_empresa'] = Inventario::select('empresa_id')
    ->selectRaw('COUNT(*) as total_productos, SUM(stock) as total_stock, SUM(stock * precio) as valor_total')
    ->with('empresa:id,nombre')
    ->groupBy('empresa_id')
    ->orderBy('empresa_id') // ✅ seguro
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
        $categoriasDisponibles = categoria::all(['id', 'nombre']);


        // ===============================
        // 🔹 CATÁLOGOS DE FILTRO (CORREGIDO)
        // ===============================

if (in_array($user->role_id, $rolesPermitidos)) {
    // 🟢 ADMINS: TODAS las sedes del sistema (no solo las que tienen inventario)
    $sedesDisponibles = Sede::all(['id', 'nombre']); // ⚡ CAMBIO AQUÍ

    // Para categorías también todas las del sistema
    $categoriasDisponibles = categoria::all(['id', 'nombre']);

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
    $categoriasDisponibles = categoria::all(['id', 'nombre']);

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
            'categorias' => $categoriasDisponibles,
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
            'responsable_id'  => $data['responsable_id'] ?? null,
            'estado_id'       => $data['estado_id'] ?? 1,
            'fecha_envio'     => now(),
            'notas'           => $data['notas'] ?? null,
        ]);

        $empresaEnvioId = $envio->empresa_id;

        $detallesRegistrados = [];
        $movimientos = []; // registros por inventario consumido (para auditoría)
        // Consumos que salieron del inventario de otra empresa distinta a la
        // dueña del envío: se registran como préstamo una vez exista el
        // MovimientoStock consolidado, más abajo.
        $prestamosPendientes = [];

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

                // Traer TODOS los inventarios de esa bodega (pueden ser varias empresas) y bloquear
                $inventariosOrigen = Inventario::where('producto_id', $productoId)
                    ->where('sede_id', $sedeOrigen)
                    ->where('bodega_id', $bodegaId)
                    ->lockForUpdate()
                    ->orderBy('stock', 'DESC') // primero los que más stock tienen
                    ->get();

                if ($inventariosOrigen->isEmpty()) {
                    throw new \Exception("Inventario no encontrado para el producto {$productoId} en la bodega {$bodegaId}.");
                }

                //  Validación consolidada
      $productoModel = product::find($productoId);
$nombreProducto = $productoModel?->name ?? "Producto {$productoId}";

$bodegaModel = Bodega::find($bodega['bodega_id']);
$nombreBodega = $bodegaModel?->nombre ?? "Bodega {$bodega['bodega_id']}";

$stockTotal = (float) $inventariosOrigen->sum('stock');

if ($stockTotal < $cantidad) {
    throw new \Exception(
        "Stock insuficiente para {$nombreProducto} en {$nombreBodega} ({$stockTotal} disponibles)."
    );
}

                //  Descontar Opción A: del primero que pueda cubrir, sino ir agotando
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
                        $this->registrarPrestamoPendiente($prestamosPendientes, $inv, $empresaEnvioId, $restante);

                        $consumos[] = ['inventario_id' => $inv->id, 'empresa_id' => $inv->empresa_id, 'consumido' => $restante];
                        $restante = 0;
                    } else {
                        // agotar este inventario y seguir
                        $inv->stock = 0;
                        $inv->save();
                        $this->registrarPrestamoPendiente($prestamosPendientes, $inv, $empresaEnvioId, $disponible);

                        $consumos[] = ['inventario_id' => $inv->id, 'empresa_id' => $inv->empresa_id, 'consumido' => $disponible];
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
                        'empresa_id'      => $c['empresa_id'],
                        'detalle_envio_id'=> $detalleEnvio->id,
                        'bodega_origen_id'=> $bodegaId,
                        'tipo'            => 'envio_interno',
                        'usuario_id'      => $user->id,
                        'detalle'         => "Envío interno #{$envio->id} desde bodega {$bodegaId} hacia sede {$data['sede_destino_id']}",
                    ];
                }
            }
        }
     // 🟢 5) Generar PDF y guardar ruta
        $pdfPath = $this->generarPdfEnvio($envio, $detallesRegistrados);
        // 🟢 3) Registrar movimiento consolidado (resumen)
        $movimientoConsolidado = MovimientoStock::create([
            'tipo'            => 'traslado_multiple',
            'envio_interno_id'=> $envio->id,
            'usuario_id'      => $user->id,
            'sede_origen_id'  => $sedeOrigen,
            'sede_destino_id' => $data['sede_destino_id'],
            'cantidad'        => collect($movimientos)->sum('cantidad'),
            'detalle'         => [
                'envio_id' => $envio->id,
                'detalles' => $movimientos,
                'fecha'    => now()->toDateTimeString(),
                'observacion' => $data['notas'] ?? 'Traslado interno entre sedes',
            ],
            'pdf_path'      => $pdfPath ?? null,
        ]);

        foreach ($prestamosPendientes as $prestamo) {
            PrestamoStockEmpresa::create([
                'movimiento_stock_id'    => $movimientoConsolidado->id,
                'orden_compra_id'        => null,
                'producto_id'            => $prestamo['producto_id'],
                'bodega_id'              => $prestamo['bodega_id'],
                'inventario_id'          => $prestamo['inventario_id'],
                'empresa_prestamista_id' => $prestamo['empresa_prestamista_id'],
                'empresa_prestataria_id' => $empresaEnvioId,
                'cantidad'               => $prestamo['cantidad'],
            ]);
        }

        // 🟢 4) Asociar órdenes (se eliminó el bloque duplicado)
        if (!empty($data['ordenes_compra'])) {
            foreach ($data['ordenes_compra'] as $ordenId) {
                Envio_orden::create([
                    'envio_interno_id' => $envio->id,
                    'orden_compra_id'  => $ordenId,
                ]);
            }
        }



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
    $filePath = 'movimientos/' . $fileName;

    Storage::disk('public')->put($filePath, $pdf->output());

    return $filePath;
}

public function regenerarPdfEnvio(Envio_internos $envio): string
{
    $envio->load([
        'empresa',
        'usuario',
        'responsable',
        'sedeOrigen',
        'sedeDestino',
        'detalles.product',
        'detalles.ordenCompra',
    ]);

    return $this->generarPdfEnvio($envio, $envio->detalles);
}

/**
 * Suma cantidad al inventario de una bodega/empresa destino, creando el
 * registro si todavía no existe.
 */
private function acreditarInventarioDestino(int $productoId, int $sedeId, int $bodegaId, ?int $empresaId, float $cantidad, $userId): Inventario
{
    $inventario = Inventario::where([
        'producto_id' => $productoId,
        'sede_id'     => $sedeId,
        'bodega_id'   => $bodegaId,
        'empresa_id'  => $empresaId,
    ])->lockForUpdate()->first();

    if (!$inventario) {
        $inventario = Inventario::create([
            'producto_id' => $productoId,
            'sede_id'     => $sedeId,
            'bodega_id'   => $bodegaId,
            'empresa_id'  => $empresaId,
            'user_id'     => $userId,
            'stock'       => 0,
            'min_stock'   => 0,
            'max_stock'   => 0,
        ]);
    }

    $inventario->increment('stock', $cantidad);

    return $inventario;
}

/**
 * Confirmar la recepción de un traslado interno: el responsable asignado
 * indica en qué bodega de la sede destino quedó cada producto y esa
 * cantidad se suma al inventario de esa bodega.
 */
public function confirmarRecepcionEnvio(int $envioId, array $detallesPayload, $user): array
{
    DB::beginTransaction();

    try {
        $envio = Envio_internos::lockForUpdate()->findOrFail($envioId);

        $esResponsable = (int) $envio->responsable_id === (int) $user->id;
        $esAdmin = in_array($user->role_id, [1, 2, 4]);

        if (!$esResponsable && !$esAdmin) {
            throw new \Exception('No tiene autorización para confirmar la recepción de este traslado.');
        }

        if ((int) $envio->estado_id === EstadoEnum::COMPLETADO->value) {
            throw new \Exception('Este traslado ya fue recibido anteriormente.');
        }

        $detallesEnvio = Detalles_envio_internos::where('envio_interno_id', $envio->id)
            ->get()
            ->keyBy('id');

        // Consumos reales de inventario que se hicieron en origen (registrados en
        // registrarEnvioConDescuento), agrupados por detalle de envío. Se usan para
        // acreditar en destino a la MISMA empresa de la que salió el stock, en vez
        // de adivinarla de nuevo acá.
        $movimientoOrigen = MovimientoStock::where('envio_interno_id', $envio->id)
            ->where('tipo', 'traslado_multiple')
            ->first();

        $consumosPorDetalle = collect($movimientoOrigen->detalle['detalles'] ?? [])
            ->groupBy('detalle_envio_id');

        $resumenMovimiento = [];

        foreach ($detallesPayload as $item) {
            $detalleId = $item['detalle_id'] ?? null;
            $detalle = $detallesEnvio->get($detalleId);

            if (!$detalle) {
                throw new \Exception("El detalle {$detalleId} no pertenece a este traslado.");
            }

            $bodegaDestinoId = (int) ($item['bodega_destino_id'] ?? 0);
            $cantidadRecibida = (float) ($item['cantidad_recibida'] ?? 0);
            $nombreProducto = $detalle->descripcion ?: "producto {$detalle->product_id}";

            if ($bodegaDestinoId <= 0) {
                throw new \Exception("Debe indicar la bodega destino para {$nombreProducto}.");
            }
            if ($cantidadRecibida <= 0) {
                throw new \Exception("La cantidad recibida debe ser mayor que 0 para {$nombreProducto}.");
            }

            $bodegaDestino = bodega::find($bodegaDestinoId);
            if (!$bodegaDestino || (int) $bodegaDestino->sede_id !== (int) $envio->sede_destino_id) {
                throw new \Exception("La bodega seleccionada no pertenece a la sede destino del traslado.");
            }

            // Reparte lo recibido entre las mismas empresas de las que salió el
            // stock en origen (puede haber más de una si esa bodega tenía
            // inventario mezclado). Si no hay consumos originales registrados
            // (envíos antiguos) o sobra cantidad recibida frente a lo que
            // consta como descontado, el excedente se acredita a la empresa
            // del envío, igual que antes.
            $consumosDetalle = $consumosPorDetalle->get($detalle->id, collect());
            $restanteRecibido = $cantidadRecibida;

            foreach ($consumosDetalle as $consumo) {
                if ($restanteRecibido <= 0) {
                    break;
                }

                $cantidadEmpresa = min((float) $consumo['cantidad'], $restanteRecibido);
                if ($cantidadEmpresa <= 0) {
                    continue;
                }

                $inventarioDestino = $this->acreditarInventarioDestino(
                    $detalle->product_id,
                    $envio->sede_destino_id,
                    $bodegaDestinoId,
                    $consumo['empresa_id'] ?? $envio->empresa_id,
                    $cantidadEmpresa,
                    $user->id
                );

                $resumenMovimiento[] = [
                    'detalle_id'        => $detalle->id,
                    'producto_id'       => $detalle->product_id,
                    'bodega_destino_id' => $bodegaDestinoId,
                    'cantidad_recibida' => $cantidadEmpresa,
                    'inventario_id'     => $inventarioDestino->id,
                ];

                $restanteRecibido -= $cantidadEmpresa;
            }

            if ($restanteRecibido > 0) {
                $inventarioDestino = $this->acreditarInventarioDestino(
                    $detalle->product_id,
                    $envio->sede_destino_id,
                    $bodegaDestinoId,
                    $envio->empresa_id,
                    $restanteRecibido,
                    $user->id
                );

                $resumenMovimiento[] = [
                    'detalle_id'        => $detalle->id,
                    'producto_id'       => $detalle->product_id,
                    'bodega_destino_id' => $bodegaDestinoId,
                    'cantidad_recibida' => $restanteRecibido,
                    'inventario_id'     => $inventarioDestino->id,
                ];
            }

            $detalle->update([
                'bodega_destino_id' => $bodegaDestinoId,
                'cantidad_recibida' => $cantidadRecibida,
            ]);
        }

        MovimientoStock::create([
            'tipo'             => 'recepcion_traslado',
            'envio_interno_id' => $envio->id,
            'usuario_id'       => $user->id,
            'sede_origen_id'   => $envio->sede_origen_id,
            'sede_destino_id'  => $envio->sede_destino_id,
            'cantidad'         => collect($resumenMovimiento)->sum('cantidad_recibida'),
            'detalle'          => [
                'envio_id' => $envio->id,
                'detalles' => $resumenMovimiento,
                'fecha'    => now()->toDateTimeString(),
                'observacion' => "Recepción confirmada del traslado #{$envio->id}",
            ],
        ]);

        $envio->update([
            'estado_id'       => EstadoEnum::COMPLETADO->value,
            'fecha_recepcion' => now(),
        ]);

        DB::commit();

        return [
            'success' => true,
            'message' => 'Recepción confirmada, el stock ya está disponible en la sede destino.',
            'envio'   => $envio->fresh([
                'sedeOrigen',
                'sedeDestino',
                'empresa',
                'usuario',
                'responsable',
                'detalles.product',
                'detalles.bodegaOrigen',
                'detalles.bodegaDestino',
            ]),
        ];
    } catch (\Throwable $e) {
        DB::rollBack();

        return [
            'success' => false,
            'message' => $e->getMessage(),
        ];
    }
}


/**
 * Si el inventario descontado pertenece a una empresa distinta a la dueña
 * de la orden, deja anotado el préstamo para registrarlo una vez exista
 * el MovimientoStock al que va a quedar asociado.
 */
private function registrarPrestamoPendiente(array &$prestamosPendientes, Inventario $inv, ?int $empresaOrdenId, float $cantidad): void
{
    if (!$empresaOrdenId || (int) $inv->empresa_id === (int) $empresaOrdenId) {
        return;
    }

    $prestamosPendientes[] = [
        'producto_id'            => $inv->producto_id,
        'bodega_id'              => $inv->bodega_id,
        'inventario_id'          => $inv->id,
        'empresa_prestamista_id' => $inv->empresa_id,
        'cantidad'               => $cantidad,
    ];
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
            $detalleId = $item['detalle_id'] ?? null;

            // El stock a descontar es el de la sede de la orden, no el de la sede
            // del usuario que cierra la orden (pueden ser distintas).
            $ordenCompra = $ordenCompraId ? Orden_Compra::find($ordenCompraId) : null;
            $sedeId = $ordenCompra?->sede_id ?? $user->sede_id;
            $empresaOrdenId = $ordenCompra?->empresa_id;

            $cantidadCubierta = 0;
            $detalleOriginal  = [];
            $equivalentesResp = [];
            // Decrementos que salieron del inventario de otra empresa distinta a
            // la dueña de la orden: quedan pendientes de registrar como préstamo
            // una vez se resuelva el movimiento de stock más abajo.
            $prestamosPendientes = [];
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
                        'mensaje'     => "Stock insuficiente: Disponible {$stockTotal}, Requerido {$cantDescontar}",

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
                        $this->registrarPrestamoPendiente($prestamosPendientes, $inv, $empresaOrdenId, $restante);
                        $detalleOriginal[] = [
                            'detalle_id'         => $detalleId,
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
                        $this->registrarPrestamoPendiente($prestamosPendientes, $inv, $empresaOrdenId, $disponible);
                        $detalleOriginal[] = [
                            'detalle_id'         => $detalleId,
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


            if (!empty($equivalentes)) {
                foreach ($equivalentes as $equivalente) {
                    $eqId   = (int) $equivalente['id'];
                    $razon  = $equivalente['razon'] ?? 'Equivalente por falta de stock';
                    $productoEq = \App\Models\Crm\product::find($eqId);

                    $eqResp = [
                        'detalle_id'       => $detalleId,
                        'producto_id'      => $eqId,
                        'producto_nombre'  => $productoEq?->name ?? "Producto #{$eqId}",
                        'razon'            => $razon,
                        'bodegas'          => [],
                        'producto_origen_id'=> $productoId,
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
                                $this->registrarPrestamoPendiente($prestamosPendientes, $invEq, $empresaOrdenId, $restante);
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
                                $this->registrarPrestamoPendiente($prestamosPendientes, $invEq, $empresaOrdenId, $disponible);
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
  $movimiento = MovimientoStock::where('orden_trabajo_id', $ordenTrabajoId)
    ->where('tipo', 'descuento_masivo')
    ->first();

if (!$movimiento) {
    $movimiento = MovimientoStock::create([
        'orden_trabajo_id' => $ordenTrabajoId,
        'orden_compra_id'  => $ordenCompraId,
        'usuario_id'       => $user->id,
        'producto_id'      => $productoId, // ← importante
        'tipo'             => 'descuento_masivo',
        'cantidad'         => 0,
        'detalle'          => [
            'bodegas'      => [],
            'equivalentes' => [],
            'errores'      => [],
        ],
        'sede_origen_id'   => $sedeId ?? null,
        'sede_destino_id'  => $sedeId ?? null,
        'envio_interno_id' => null,
        'razon'            => 'Descuento masivo',
    ]);
}


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

if ($detalleId && $cantidadCubierta > 0) {
    Orden_Compra_Detalle::where('id', $detalleId)
        ->increment('cantidad_ejecutada_kg', $cantidadCubierta);
}

foreach ($prestamosPendientes as $prestamo) {
    PrestamoStockEmpresa::create([
        'movimiento_stock_id'    => $movimiento->id,
        'orden_compra_id'        => $ordenCompraId,
        'producto_id'            => $prestamo['producto_id'],
        'bodega_id'              => $prestamo['bodega_id'],
        'inventario_id'          => $prestamo['inventario_id'],
        'empresa_prestamista_id' => $prestamo['empresa_prestamista_id'],
        'empresa_prestataria_id' => $empresaOrdenId,
        'cantidad'               => $prestamo['cantidad'],
    ]);
}

            $resultados[] = [
                'producto_id'        => $productoId,
                'cantidad_requerida' => $cantidadTotal,
                'detalle_original'   => $detalleOriginal,
                'equivalentes'       => $equivalentesResp,
                'errores'            => $errores,
                'faltante'           => $faltante,
                'orden_trabajo_id'   => $ordenTrabajoId,
                'prestamos_entre_empresas' => $prestamosPendientes,
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
// Agrupar los resultados por orden de trabajo
$agrupadoPorOrden = collect($resultados)
    ->groupBy('orden_trabajo_id')
    ->toArray();

$pdfsGenerados = [];

foreach ($agrupadoPorOrden as $ordenId => $resultadosOrden) {
    $pathPdf = $this->generarPDFMovimientoPorOrden($ordenId, $resultadosOrden, $user);
$mov = \App\Models\Crm\MovimientoStock::where('orden_trabajo_id', $ordenId)
    ->where('tipo', 'descuento_masivo')
    ->first();

if ($mov) {
    $mov->update([
        'pdf_path' => $pathPdf
    ]);
}

$pdfsGenerados[] = [
    'orden_trabajo_id' => $ordenId,
    'pdf' => asset("storage/{$pathPdf}")
];
}

return [
    'success'   => empty($erroresGlobales),
    'resultados'=> $resultados,
    'errores'   => $erroresGlobales,
    'pdfs'      => $pdfsGenerados
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


public function generarPDFMovimientoPorOrden($ordenId, array $resultados, $usuario)
{
    $orden = OrdenDeTrabajo::find($ordenId);

    $data = [
        'usuario'   => $usuario,
        'resultados'=> $resultados,
        'orden'     => $orden,
        'fecha'     => now()->format('d/m/Y H:i'),
    ];

    $pdf = Pdf::loadView('pdf.movimiento_por_orden', $data)
        ->setPaper('A4', 'portrait');

    $filename = 'OT_' . str_pad($ordenId, 4, '0', STR_PAD_LEFT) . '_' . now()->format('Ymd_His') . '.pdf';
    $path = 'movimientos/' . $filename;

    Storage::disk('public')->put($path, $pdf->output());

    return $path;
}


//Anular Movimiento de Stock
public function anularMovimiento($movimientoId, $usuario)
{
    $mov = MovimientoStock::findOrFail($movimientoId);

    if ($mov->anulado) {
        return [
            'success' => false,
            'message' => 'Este movimiento ya fue anulado.'
        ];
    }

    DB::beginTransaction();
    try {

        $detalle = $mov->detalle ?? [];
        $errores = [];

        foreach ($detalle['bodegas'] ?? [] as $d) {

            if (!isset($d['inventario_id'])) {
                $errores[] = [
                    'detalle' => $d,
                    'error' => 'No existe inventario_id en este movimiento.'
                ];
                continue;
            }

            $inv = Inventario::lockForUpdate()->find($d['inventario_id']);
            if (!$inv) continue;

            $cantidad = (float) $d['cantidad_descontada'];

           $tiposQueSumaron = [
    'ingreso',
    'entrada',
    'entrada_masiva',
    'import_excel',
    'ajuste_positivo',
    'entrada_manual'
];

// Si el movimiento SUMÓ stock → restarlo
if (in_array($mov->tipo, $tiposQueSumaron)) {
    $inv->decrement('stock', $cantidad);
}
// Si el movimiento RESTÓ stock → devolverlo
else {
    $inv->increment('stock', $cantidad);
}

        }

        // Mantener sincronizada la ejecución de cada línea al reversar un
        // descuento. Los movimientos nuevos guardan detalle_id tanto para el
        // producto original como para sus equivalentes.
        $cantidadesPorDetalle = collect($detalle['bodegas'] ?? [])
            ->concat(collect($detalle['equivalentes'] ?? [])->flatMap(
                fn ($equivalente) => collect($equivalente['bodegas'] ?? [])->map(
                    fn ($bodega) => array_merge($bodega, [
                        'detalle_id' => $equivalente['detalle_id'] ?? null,
                    ])
                )
            ))
            ->filter(fn ($item) => ! empty($item['detalle_id']))
            ->groupBy('detalle_id')
            ->map(fn ($items) => $items->sum(
                fn ($item) => (float) ($item['cantidad_descontada'] ?? 0)
            ));

        foreach ($cantidadesPorDetalle as $detalleId => $cantidad) {
            $ordenDetalle = Orden_Compra_Detalle::lockForUpdate()->find($detalleId);
            if (! $ordenDetalle) {
                continue;
            }

            $ordenDetalle->cantidad_ejecutada_kg = max(
                0,
                (float) $ordenDetalle->cantidad_ejecutada_kg - $cantidad
            );
            $ordenDetalle->save();
        }

        // Crear movimiento reverso
        $movReverso = MovimientoStock::create([
            'orden_trabajo_id' => $mov->orden_trabajo_id,
            'orden_compra_id'  => $mov->orden_compra_id,
            'usuario_id'       => $usuario->id,
            'tipo'             => 'anulacion',
            'cantidad'         => $mov->cantidad,
            'detalle'          => $mov->detalle,
            'razon'            => "Anulación del movimiento #{$mov->id}",
        ]);

        // Generar PDF para el reverso
        $pathPdf = $this->generarPDFMovimientoPorOrden(
            $mov->orden_trabajo_id,
            [$movReverso],
            $usuario
        );

        $movReverso->update([
            'pdf_path' => $pathPdf
        ]);

        // Marcar movimiento original como anulado
        $mov->update([
            'anulado' => true,
            'razon'   => "Anulado por {$usuario->name}"
        ]);

        DB::commit();

        return [
            'success' => true,
            'message' => 'Movimiento anulado correctamente.',
            'movimiento_original' => $mov,
            'movimiento_reverso'  => $movReverso,
            'pdf' => asset("storage/{$pathPdf}"),
            'errores' => $errores
        ];

    } catch (\Throwable $e) {

        DB::rollBack();

        return [
            'success' => false,
            'message' => 'Error al anular el movimiento.',
            'error'   => $e->getMessage(),
        ];
    }
}


// En InventarioService.php - método listarMovimientos

/**
 * Filtros compartidos entre listarMovimientos() y exportarMovimientos(), para
 * que la exportación siempre traiga exactamente lo que el usuario ve filtrado
 * en pantalla.
 */
private function construirQueryMovimientos(Request $request, $user)
{
    $rolesPermitidos = [1, 4];
    $isAdmin = in_array($user->role_id, $rolesPermitidos);

    $query = MovimientoStock::with([
        'producto:id,name,code',
        'usuario:id,name',
        'ordenTrabajo:id',
        'ordenCompra:id,orden_compra_cliente',
        'prestamos.empresaPrestamista:id,nombre',
        'prestamos.empresaPrestataria:id,nombre',
    ]);

    // ======================
    // 🔹 Filtros por fechas
    // ======================
    if ($request->filled('desde')) {
        $query->whereDate('created_at', '>=', $request->desde);
    }

    if ($request->filled('hasta')) {
        $query->whereDate('created_at', '<=', $request->hasta);
    }

    // ======================
    // 🔹 Filtro por producto
    // ======================
    if ($request->filled('producto')) {
        $p = $request->producto;
        $query->whereHas('producto', function ($q) use ($p) {
            $q->where('name', 'LIKE', "%$p%")
              ->orWhere('code', 'LIKE', "%$p%");
        });
    }

    // ======================
    // 🔹 Tipo de movimiento
    // ======================
    if ($request->filled('tipo')) {
        $query->where('tipo', $request->tipo);
    }

    // ======================
    // 🔹 Usuario que ejecutó
    // ======================
    if ($request->filled('usuario_id')) {
        $query->where('usuario_id', $request->usuario_id);
    }

    // ======================
    // 🔹 Sede origen / destino
    // ======================
    if ($request->filled('sede_origen_id')) {
        $query->where('sede_origen_id', $request->sede_origen_id);
    }

    if ($request->filled('sede_destino_id')) {
        $query->where('sede_destino_id', $request->sede_destino_id);
    }

    // ======================
    // 🔹 Bodega origen
    // ======================
    if ($request->filled('bodega_origen_id')) {
        $query->where('bodega_origen_id', $request->bodega_origen_id);
    }

    // ======================
    // 🔹 Orden de trabajo o compra
    // ======================
    if ($request->filled('orden_trabajo_id')) {
        $query->where('orden_trabajo_id', $request->orden_trabajo_id);
    }

    if ($request->filled('orden_compra_id')) {
        $query->where('orden_compra_id', $request->orden_compra_id);
    }

    // ======================
    // 🔹 Solo movimientos con préstamo entre empresas
    // ======================
    if ($request->boolean('solo_prestamos')) {
        $query->whereHas('prestamos');
    }

    // ======================
    // 🔹 Estado (anulado / activo)
    // ======================
    if ($request->filled('estado')) {
        if ($request->estado == 'anulado') {
            $query->where('anulado', 1);
        } else {
            $query->where('anulado', 0);
        }
    }

    // ======================
    // 🔹 Control de acceso por rol
    // ======================
    if (!$isAdmin) {
        // Usuarios solo ven movimientos de su sede
        $query->where(function ($q) use ($user) {
            $q->where('sede_origen_id', $user->sede_id)
              ->orWhere('sede_destino_id', $user->sede_id);
        });
    }

    return $query->orderBy('created_at', 'DESC');
}

public function listarMovimientos(Request $request, $user)
{
    $query = $this->construirQueryMovimientos($request, $user);

    $movimientos = $query->paginate($request->get('per_page', 20));

    // 🔥 AGREGAR URL COMPLETA DEL PDF A CADA MOVIMIENTO
    $movimientos->getCollection()->transform(function ($mov) {
        $mov->pdf_url = $mov->pdf_path
            ? asset('storage/' . $mov->pdf_path)
            : null;

        return $mov;
    });

    // ✅ RETORNAR ESTRUCTURA CORRECTA PARA EL FRONTEND
    return [
        'success' => true,
        'data' => $movimientos->items(), // Los datos actuales de la página
        'meta' => [
            'current_page' => $movimientos->currentPage(),
            'from' => $movimientos->firstItem(),
            'last_page' => $movimientos->lastPage(),
            'per_page' => $movimientos->perPage(),
            'to' => $movimientos->lastItem(),
            'total' => $movimientos->total(),
            'total_registros' => $movimientos->total(),
            'prev_page_url' => $movimientos->previousPageUrl(),
            'next_page_url' => $movimientos->nextPageUrl(),
        ]
    ];
}

/**
 * Mismos filtros que listarMovimientos(), sin paginar, para exportar a Excel.
 */
public function exportarMovimientos(Request $request, $user)
{
    return $this->construirQueryMovimientos($request, $user)->get();
}

/**
 * Listado paginado de préstamos de stock entre empresas, agrupado por
 * movimiento (un movimiento de descuento/traslado puede haber tomado stock
 * prestado de varios items/bodegas a la vez). El detalle item por item queda
 * para el PDF de cada movimiento, no para esta tabla.
 */
public function listarPrestamosEntreEmpresas(Request $request)
{
    $filtro = $this->filtrosPrestamosEntreEmpresas($request);

    $grupos = (clone $filtro)
        ->select('movimiento_stock_id')
        ->selectRaw('MAX(created_at) as fecha')
        ->selectRaw('COUNT(*) as total_items')
        ->selectRaw('SUM(cantidad) as total_cantidad')
        ->groupBy('movimiento_stock_id')
        ->orderByDesc('fecha')
        ->paginate($request->get('per_page', 20));

    $movimientoIds = collect($grupos->items())->pluck('movimiento_stock_id');

    $movimientos = MovimientoStock::whereIn('id', $movimientoIds)
        ->with(['usuario:id,name', 'ordenCompra:id,orden_compra_cliente'])
        ->get()
        ->keyBy('id');

    $empresasPorMovimiento = PrestamoStockEmpresa::with([
        'empresaPrestamista:id,nombre',
        'empresaPrestataria:id,nombre',
    ])
        ->whereIn('movimiento_stock_id', $movimientoIds)
        ->get()
        ->groupBy('movimiento_stock_id');

    $data = collect($grupos->items())->map(function ($g) use ($movimientos, $empresasPorMovimiento) {
        $mov = $movimientos->get($g->movimiento_stock_id);

        $empresas = $empresasPorMovimiento->get($g->movimiento_stock_id, collect())
            ->map(fn ($p) => [
                'empresa_prestamista' => $p->empresaPrestamista?->nombre,
                'empresa_prestataria' => $p->empresaPrestataria?->nombre,
            ])
            ->unique()
            ->values();

        return [
            'movimiento_stock_id' => $g->movimiento_stock_id,
            'tipo' => $mov?->tipo,
            'usuario' => $mov?->usuario,
            'orden_trabajo_id' => $mov?->orden_trabajo_id,
            'orden_compra' => $mov?->ordenCompra,
            'orden_compra_id' => $mov?->orden_compra_id,
            'total_items' => $g->total_items,
            'total_cantidad' => $g->total_cantidad,
            'empresas' => $empresas,
            'fecha' => $g->fecha,
        ];
    });

    return [
        'success' => true,
        'data' => $data,
        'meta' => [
            'current_page' => $grupos->currentPage(),
            'last_page' => $grupos->lastPage(),
            'per_page' => $grupos->perPage(),
            'total' => $grupos->total(),
        ],
    ];
}

/**
 * Mismos filtros que listarPrestamosEntreEmpresas(), sin agrupar ni paginar,
 * para exportar a Excel (ahí sí interesa el detalle item por item).
 */
public function exportarPrestamosEntreEmpresas(Request $request)
{
    return $this->filtrosPrestamosEntreEmpresas($request)
        ->with([
            'producto:id,name,code',
            'bodega:id,nombre',
            'empresaPrestamista:id,nombre',
            'empresaPrestataria:id,nombre',
            'ordenCompra:id,orden_compra_cliente',
            'movimientoStock:id,tipo,orden_trabajo_id,created_at',
        ])
        ->orderBy('created_at', 'DESC')
        ->get();
}

/**
 * Genera (sin guardar en disco) el PDF con todos los items de préstamo entre
 * empresas que ocurrieron dentro de un mismo movimiento de stock.
 */
public function generarPdfPrestamosPorMovimiento(int $movimientoStockId)
{
    $movimiento = MovimientoStock::with(['usuario:id,name', 'ordenTrabajo:id', 'ordenCompra:id,orden_compra_cliente'])
        ->findOrFail($movimientoStockId);

    $items = PrestamoStockEmpresa::with([
        'producto:id,name,code',
        'bodega:id,nombre',
        'empresaPrestamista:id,nombre',
        'empresaPrestataria:id,nombre',
    ])
        ->where('movimiento_stock_id', $movimientoStockId)
        ->get();

    if ($items->isEmpty()) {
        throw new \Exception('Este movimiento no tiene préstamos entre empresas registrados.');
    }

    return Pdf::loadView('pdf.prestamo_stock_empresa', [
        'movimiento' => $movimiento,
        'items' => $items,
    ]);
}

private function filtrosPrestamosEntreEmpresas(Request $request)
{
    $query = PrestamoStockEmpresa::query();

    if ($request->filled('empresa_prestamista_id')) {
        $query->where('empresa_prestamista_id', $request->empresa_prestamista_id);
    }

    if ($request->filled('empresa_prestataria_id')) {
        $query->where('empresa_prestataria_id', $request->empresa_prestataria_id);
    }

    if ($request->filled('producto_id')) {
        $query->where('producto_id', $request->producto_id);
    }

    if ($request->filled('orden_compra_id')) {
        $query->where('orden_compra_id', $request->orden_compra_id);
    }

    if ($request->filled('desde')) {
        $query->whereDate('created_at', '>=', $request->desde);
    }

    if ($request->filled('hasta')) {
        $query->whereDate('created_at', '<=', $request->hasta);
    }

    if ($request->filled('compensado')) {
        $query->where('compensado', $request->boolean('compensado'));
    }

    return $query;
}

}
