<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StockMasivoRequest;
use App\Http\Requests\Crm\StockRequest;
use App\Models\Crm\bodega;
use App\Models\Crm\Inventario;
use App\Models\Crm\MovimientoStock;
use App\Models\Crm\product;
use App\Services\Crm\InventarioService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InventorieController extends Controller
{
    /**
     * Display a listing of the resource with advanced filters.
     */
 /**
 * Listar inventarios con filtros, estadísticas y totales globales
 */
protected $inventarioService;
protected $movimientoPDFService;

    public function __construct(InventarioService $inventarioService, InventarioService $movimientoPDFService)
    {
        $this->inventarioService = $inventarioService;
        $this->movimientoPDFService = $movimientoPDFService;

    }

 public function index(Request $request)
{
    try {
        $user = auth()->user();

        $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'sede_id' => 'integer|exists:sedes,id',
            'bodega_id' => 'integer|exists:bodegas,id',
            'empresa_id' => 'integer|exists:empresas,id',
            'producto' => 'string|max:255',
            'stock_min' => 'integer|min:0',
            'stock_max' => 'integer|min:0',
            'ultimo_movimiento_dias' => 'integer|min:1',
            'vencimiento_dias' => 'integer|min:1',
            'stock_bajo' => 'boolean',
            'order_by' => 'string|in:stock,precio,updated_at,created_at,fecha_vencimiento',
            'order_direction' => 'string|in:asc,desc',
        ]);

        $resultado = $this->inventarioService->listarInventarios($request, $user);

        // ✅ Manejo seguro en caso de error interno
        if (isset($resultado['error']) && $resultado['error'] === true) {
            throw new \Exception($resultado['detalle'] ?? 'Error interno al listar inventarios');
        }

        $inventarios = $resultado['inventarios'];

        return response()->json([
            'success' => true,
            'data' => $inventarios->items(),
            'pagination' => [
                'current_page' => $inventarios->currentPage(),
                'last_page' => $inventarios->lastPage(),
                'per_page' => $inventarios->perPage(),
                'total_registros' => $inventarios->total(),
                'from' => $inventarios->firstItem(),
                'to' => $inventarios->lastItem(),
                'totales_globales' => $resultado['totales'],
            ],
            'estadisticas' => $resultado['estadisticas'],
            'estadisticas_por_filtro' => $resultado['estadisticas_por_filtro'],
            'ultimos_movimientos' => $resultado['ultimos_movimientos'],
            'sedes_disponibles' => $resultado['sedes'],
            'bodegas_disponibles' => $resultado['bodegas'],
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Datos de entrada inválidos',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error en InventorieController@index:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => $user->id ?? null,
            'request_params' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al obtener inventarios',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * ✅ NUEVO: Obtener opciones para filtros
     */
    public function getFilterOptions()
    {
        try {
            $user = auth()->user();

            $rolesPermitidos = [1, 2];

            $baseQuery = Inventario::query();
            
            // Filtrar por sede del usuario si no tiene permisos completos
            if (!in_array($user->roles_id, $rolesPermitidos)) {
                $baseQuery->where('sede_id', $user->sede_id);
            }

            $opciones = [
                'sedes' => $baseQuery->distinct()->with('sede:id,nombre')->get()->pluck('sede')->unique('id')->values(),
                'bodegas' => $baseQuery->distinct()->with('bodega:id,nombre')->get()->pluck('bodega')->unique('id')->values(),
                'empresas' => $baseQuery->distinct()->with('empresa:id,nombre')->get()->pluck('empresa')->unique('id')->values(),
                'productos' => $baseQuery->distinct()->with('producto:id,name,code')->get()->pluck('producto')->unique('id')->values(),
            ];

            return response()->json([
                'success' => true,
                'data' => $opciones
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener opciones de filtros',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NUEVO: Exportar inventarios con filtros
     */
    public function export(Request $request)
    {
        // Reutilizar la lógica del index para aplicar los mismos filtros
        $user = auth()->user();
        $rolesPermitidos = [1, 2];

        $query = Inventario::with(['producto', 'empresa', 'sede', 'bodega']);

        if (!in_array($user->roles_id, $rolesPermitidos)) {
            $query->where('sede_id', $user->sede_id);
        }

        // Aplicar todos los filtros del request...
        // (mismo código de filtros del index)

        $inventarios = $query->get();

        return response()->json([
            'success' => true,
            'data' => $inventarios,
            'total_registros' => $inventarios->count(),
            'fecha_exportacion' => now()->format('Y-m-d H:i:s')
        ]);
    }

    
public function descontarStock(StockRequest $request) 
{
    $user   = auth()->user();
    $sedeId = $user->sede_id;

    return DB::transaction(function () use ($request, $sedeId, $user) {
        $cantidadTotal    = (int) $request->cantidad;
        $cantidadCubierta = 0;

        $detalleOriginal   = [];
        $equivalentesResp  = [];
        $errores           = [];

        // ----------------------------------------------------
        // 🔹 1. Descontar bodegas del producto original
        // ----------------------------------------------------
     
// ✅ 1. Descontar bodegas del producto original (multi inventario)
foreach ($request->input('bodegas', []) as $bodega) {

    $bodegaId      = (int) $bodega['bodega_id'];
    $cantDescontar = (float) $bodega['cantidad'];

    // 🔒 Traer TODOS los inventarios (si hay varias empresas)
    $inventariosOrigen = Inventario::where('producto_id', $request->producto_id)
        ->where('sede_id', $sedeId)
        ->where('bodega_id', $bodegaId)
        ->lockForUpdate()
        ->orderBy('stock', 'DESC') // primero los que más stock tienen
        ->get();

    if ($inventariosOrigen->isEmpty()) {
        $errores[] = [
            'producto_id'   => $request->producto_id,
            'bodega_id'     => $bodegaId,
            'mensaje'       => "No hay inventario disponible en la bodega seleccionada"
        ];
        continue;
    }

    $stockTotal = (float) $inventariosOrigen->sum('stock');

    if ($stockTotal < $cantDescontar) {
        $errores[] = [
            'producto_id'   => $request->producto_id,
            'bodega_id'     => $bodegaId,
            'mensaje'       => "Stock insuficiente: Disponible {$stockTotal}, Requerido {$cantDescontar}"
        ];
        continue;
    }

    // ✅ descontar prorrateado
    $restante = $cantDescontar;

    foreach ($inventariosOrigen as $inv) {
        if ($restante <= 0) break;

        $disponible = (float) $inv->stock;
        if ($disponible <= 0) continue;

        if ($disponible >= $restante) {
            $inv->decrement('stock', $restante);
            $bodegaNombre = bodega::find($bodegaId)->nombre ?? 'N/A';
            $productNombre = product::find($request->producto_id)->name ?? 'N/A';
            $detalleOriginal[] = [
                'producto_id'        => $request->producto_id,
                'producto_nombre'    => $productNombre,
                'bodega_nombre'      => $bodegaNombre,
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
                'producto_id'        => $request->producto_id,
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

     // ----------------------------------------------------
// 🔹 2. Descontar equivalentes si hay déficit (multi inventario)
// ----------------------------------------------------
$deficit = max(0, $cantidadTotal - $cantidadCubierta);

if ($deficit > 0) {
    foreach ($request->input('producto_equivalentes', []) as $equivalente) {

        $eqId   = (int) $equivalente['id'];
        $razon  = $equivalente['razon'] ?? 'Equivalente por falta de stock';
        $productoEq = product::find($eqId);
      $eqResp = [
    'producto_id'      => $eqId,
    'producto_nombre'  => $productoEq?->name ?? "Producto #{$eqId}",
    'razon'            => $razon,
    'bodegas'          => []
];

foreach ($equivalente['bodegas'] as $bodegaEq) {
    $bodegaId = (int) $bodegaEq['bodega_id'];
    $cantEq   = (float) $bodegaEq['cantidad'];

    // ✅ obtener nombre de la bodega
    $bodegaNombreEq = bodega::find($bodegaId)->nombre ?? 'N/A';

    // 🔒 traer inventarios
    $inventariosEq = Inventario::where('producto_id', $eqId)
        ->where('sede_id', $sedeId)
        ->where('bodega_id', $bodegaId)
        ->lockForUpdate()
        ->orderBy('stock', 'DESC')
        ->get();

    if ($inventariosEq->isEmpty()) {
        $eqResp['bodegas'][] = [
            'bodega_id'     => $bodegaId,
            'bodega_nombre' => $bodegaNombreEq,
            'error'         => "No hay inventario disponible en esta bodega para el equivalente",
        ];
        continue;
    }

    $stockTotalEq = (float) $inventariosEq->sum('stock');

    if ($stockTotalEq < $cantEq) {
        $eqResp['bodegas'][] = [
            'bodega_id'     => $bodegaId,
            'bodega_nombre' => $bodegaNombreEq,
            'error'         => "Stock insuficiente. Disponible {$stockTotalEq}, Requerido {$cantEq}"
        ];
        continue;
    }

    // ✅ Descontar stock prorrateado
    $restante = $cantEq;
    foreach ($inventariosEq as $invEq) {
        if ($restante <= 0) break;

        $disponible = (float) $invEq->stock;
        if ($disponible <= 0) continue;

        if ($disponible >= $restante) {
            $invEq->decrement('stock', $restante);
            $eqResp['bodegas'][] = [
                'bodega_id'          => $bodegaId,
                'bodega_nombre'      => $bodegaNombreEq,
                'inventario_id'      => $invEq->id,
                'cantidad_descontada'=> $restante,
                'stock_restante'     => $invEq->stock,
            ];
            $restante = 0;
        } else {
            $invEq->decrement('stock', $disponible);
            $eqResp['bodegas'][] = [
                'bodega_id'          => $bodegaId,
                'bodega_nombre'      => $bodegaNombreEq,
                'inventario_id'      => $invEq->id,
                'cantidad_descontada'=> $disponible,
                'stock_restante'     => $invEq->stock,
            ];
            $restante -= $disponible;
        }
    }
}

        $equivalentesResp[] = $eqResp;
    }
}

// ----------------------------------------------------
// 🔹 2.1 Recalcular cobertura total (productos + equivalentes)
// ----------------------------------------------------
$cantidadCubierta = array_sum(array_column($detalleOriginal, 'cantidad_descontada'))
    + collect($equivalentesResp)
        ->flatMap(fn($eq) => array_column($eq['bodegas'], 'cantidad_descontada'))
        ->sum();

// Calcular nuevamente el faltante real
$faltante = max(0, $cantidadTotal - $cantidadCubierta);



        // ----------------------------------------------------
        // 🔹 3. Consolidar o crear movimiento global por orden
        // ----------------------------------------------------
        $movimiento = MovimientoStock::firstOrCreate(
            [
                'orden_trabajo_id' => $request->orden_trabajo_id,
                'tipo'             => 'descuento',
            ],
            [
                'orden_compra_id'  => $request->orden_compra_id,
                'usuario_id'       => $user->id,
                'cantidad'         => 0,
                'detalle'          => [
                    'bodegas'      => [],
                    'equivalentes' => [],
                    'errores'      => [],
                ],
            ]
        );

        // Mezclar los nuevos descuentos con los anteriores
        $detalleActual = $movimiento->detalle ?? [
            'bodegas'      => [],
            'equivalentes' => [],
            'errores'      => [],
        ];

        $detalleActual['bodegas']      = array_merge($detalleActual['bodegas'], $detalleOriginal);
        $detalleActual['equivalentes'] = array_merge($detalleActual['equivalentes'], $equivalentesResp);
        $detalleActual['errores']      = array_merge($detalleActual['errores'], $errores);

        // Actualizar movimiento global
        $movimiento->update([
            'detalle'  => $detalleActual,
            'cantidad' => $movimiento->cantidad + $cantidadTotal,
        ]);

        // ----------------------------------------------------
        // 🔹 4. Generar o actualizar PDF global
        // ----------------------------------------------------
$pdf = Pdf::loadView('pdf.movimiento_stock', [
    'movimiento'       => $movimiento,
    'detalleOriginal'  => $detalleActual['bodegas'],
    'equivalentesResp' => $detalleActual['equivalentes'],
    'errores'          => $detalleActual['errores'],
    'usuario'          => $user,
    'fecha'            => now()->format('d/m/Y H:i'),
]);

$fileName = "movimientos/movimiento_stock_{$movimiento->id}.pdf";
Storage::disk('public')->put($fileName, $pdf->output());

$movimiento->update(['pdf_path' => $fileName]);

        // ----------------------------------------------------
        // 🔹 5. Construir respuesta para el frontend
        // ----------------------------------------------------
   
        Log::info('RESUMEN DESCUENTO', [
            'cantidad_total' => $cantidadTotal,
            'cantidad_cubierta' => $cantidadCubierta,
            'faltante' => $faltante,
        ]);

        return response()->json([
            'success'            =>true,
            'message'            => $faltante === 0
                ? "Stock descontado exitosamente"
                : "Faltan {$faltante} unidades por cubrir",
            'producto_id'        => $request->producto_id,
            'cantidad_requerida' => $cantidadTotal,
            'detalle_original'   => $detalleOriginal,
            'equivalentes'       => $equivalentesResp,
            'errores'            => $errores,
            'faltante'           => $faltante,
            'movimiento_global'  => [
                'id'   => $movimiento->id,
                'pdf'  => asset("storage/{$movimiento->pdf_path}"),
            ]
        ],200);
    });
}

public function descontarStockMasivo(StockMasivoRequest $request)
{
    $user = auth()->user();
    $items = $request->input('items', []); // array de productos a descontar

    $result = $this->inventarioService->descontarStockMasivo($items, $user);



    return response()->json($result, $result['success'] ? 200 : 400);
}


}