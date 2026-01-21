<?php

namespace App\Http\Controllers\Crm;

use App\Exports\PlantillaProductosExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\RegistrarInventarioResquest;
use App\Http\Requests\Crm\StockRequest;
use App\Http\Requests\Crm\StoreExcelProductRequest;
use App\Http\Requests\Crm\StoreProductRequest;
use App\Http\Requests\Crm\UpdateProductRequest;
use App\Http\Requests\Traslados\StockMasivoRequest;
use App\Models\Crm\bodega;
use App\Models\Crm\categoria;
use App\Models\Crm\empresa;
use App\Models\Crm\Inventario;
use App\Models\Crm\MovimientoStock;
use App\Models\Crm\product;
use App\Models\Crm\ProductoEquivalentes;
use App\Models\Crm\Sede;
use App\Services\Crm\ProductImportResultExport;
use App\Services\Crm\ProductImportService;
use App\Services\PdfEtiquetas;
use App\Services\ProductService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

use PhpParser\Node\Stmt\TryCatch;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * 
     */
    protected $productService;
    protected $siigoService;
    protected $siigoGlobalService;

    public function __construct(\App\Services\ProductService $productService, \App\Services\SiigoGlobalService $siigoService,
     \App\Services\SiigoGlobalService $siigoGlobalService)
    {
        $this->productService = $productService;
        $this->siigoService = $siigoService;
        $this->siigoGlobalService = $siigoGlobalService;
    }

 public function index (Request $request)
    {try{
        $user = auth()->user();
        $products = $this->productService->getProducts($request, $user);

        return response()->json([
            'message' => 'Listado de productos',
            'data' => $products
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al obtener los productos',
            'error' => $e->getMessage()
        ], 500);
    }
}



//Crear Producto

public function createProduct(Request $request)
{
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'categoria_id' => 'required|exists:categorias,id',
    ]);

    try {
        DB::beginTransaction();

        // Obtener categoría
        $categoria = categoria::findOrFail($validatedData['categoria_id']);

        // Prefijo: primeras 2 letras del nombre
        $prefix = strtoupper(Str::substr($categoria->nombre, 0, 2));

        // Último código generado para esa categoría
        $lastProduct = Product::where('categoria_id', $categoria->id)
            ->where('code', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();

        // Consecutivo
        $nextNumber = 1;
        if ($lastProduct) {
            $lastNumber = intval(substr($lastProduct->code, 3));
            $nextNumber = $lastNumber + 1;
        }

 $code = $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);


        // Crear producto
        $product = Product::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'categoria_id' => $categoria->id,
            'code' => $code,
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Producto creado exitosamente',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'categoria_id' => $product->categoria_id,
                'code' => $product->code,
            ]
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Error al crear el producto',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function crearProductosExcel(ProductImportService $importService, Request $request)
{

 //  dd($request->all());
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls',
        'categoria_id' => 'required|exists:categorias,id',
    ],
    [
        'file.required' => 'El archivo es obligatorio.',
        'file.file' => 'El archivo debe ser un archivo válido.',
        'file.mimes' => 'El archivo debe ser un archivo de Excel (xlsx, xls).',
        'categoria_id.required' => 'La categoría es obligatoria.',
        'categoria_id.exists' => 'La categoría seleccionada no existe.',
    ]);
    $result = $importService->importFromExcel($request->file('file'), $request->input('categoria_id'));

    return Excel::download(
        new ProductImportResultExport($result['created'], $result['errors']),
        'resultado_importacion_productos.xlsx'
    );

}


public function stock($productoId, Request $request)
{
    $user     = $request->user();
    $bodegaId = $request->query('bodega_id'); 
    $sedeId   = null; // por defecto null → usa la sede del auth

    // Solo algunos roles pueden consultar stock de otra sede
    $rolesPermitidos = [1, 2,4]; // Ejemplo: 1=super_admin, 2=company_admin

    if (in_array($user->role_id, $rolesPermitidos)) {
        $sedeId = $request->query('sede_id'); // opcional en el request
    }

    $stock = $this->productService->getStockByProduct($productoId, $user, $bodegaId, $sedeId);

    return response()->json([
        'producto_id' => (string) $productoId,
        'sede_id'     => $sedeId ?: $user->sede_id,
        'bodega_id'   => $bodegaId,
        'stock'       => $stock,
    ]);
}


public function stockForUserAndOrder($productoId, Request $request)
{
    $user        = auth()->user();
    $orderSedeId = $request->query('order_sede_id'); // sede de la orden
    $bodegaId    = $request->query('bodega_id');     // opcional

    $stock = $this->productService->getStockForUserAndOrder(
        $productoId,
        $user,
        $orderSedeId,
        $bodegaId
    );

    return response()->json([
        'producto_id' => $productoId,
        'user_sede'   => $stock['user_sede'],
        'order_sede'  => $stock['order_sede'],
    ]);
}


public function getAllProducts(Request $request)
{
    try{

         return product::query()
        ->when($request->search, fn($q) =>
            $q->where('name', 'like', "%{$request->search}%")
            //TAmbien por descripcion y código
            ->orWhere('description', 'like', "%{$request->search}%")
              ->orWhere('code', 'like', "%{$request->search}%")
        )
         
        ->limit(50) // para no saturar la red
        ->get();

        return response()->json([
            'message' => 'Listado de productos',
            'data' => $products
        ], 200);


    }catch(\Exception $e){
        return response()->json([
            'message' => 'Error al obtener los productos',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
     try{
       $product = product::create($request->validated());

       if($request->has('inventarios')){
        foreach($request->inventarios as $inv){
             Inventario::create([
                'producto_id' => $product->id,
                'empresa_id' => $inv['empresa_id'],
                'sede_id' => $inv['sede_id'],
                'bodega_id' => $inv['bodega_id'],
                'stock' => $inv['stock'] ?? 0,
                'precio' => $inv['precio'] ?? null,
                'min_stock' => $inv['min_stock'] ?? 0,
                'max_stock' => $inv['max_stock'] ?? 0,
                'fecha_vencimiento' => $inv['fecha_vencimiento'] ?? null,
             ]);
        }
       }
return response()->json([
    'message' => 'Producto creado exitosamente',
    'data' => $product->load('inventarios.empresa', 'inventarios.sede', 'inventarios.bodega')
], 201);
     } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el producto', 'error' => $e->getMessage()], 500);
    
     }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
        return response()->json(['data' => $product], 200);
    }
   /**
   * Actualizar un producto específico.     
   *
   *  */

   public function edit(Request $request, string $id)
   {
       $product = product::find($id);
       if (!$product) {
           return response()->json(['message' => 'Producto no encontrado'], 404);
       }
       $product->update($request->all());
       return response()->json(['data' => $product], 200);
   }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, string $id)
    {
         try{
        $product = product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
        $product->update($request->validated());

        // Actualizar inventarios 
        if($request->has('inventarios')){
            foreach($request->inventarios as $iv){
                Inventario::updateOrCreate(
                    [
                        'producto_id' => $product->id,
                        'empresa_id' => $iv['empresa_id'],
                        'sede_id' => $iv['sede_id'],
                        'bodega_id' => $iv['bodega_id'],
                    ],
                    [
                        'stock' => $iv['stock'] ?? 0,
                        'precio' => $iv['precio'] ?? null,
                        'min_stock' => $iv['min_stock'] ?? 0,
                        'max_stock' => $iv['max_stock'] ?? 0,
                        'fecha_vencimiento' => $iv['fecha_vencimiento'] ?? null,
                    ]
                );
            }

        return response()->json([
            'message' => 'Producto actualizado exitosamente',
            'data' => $product->load('inventarios.empresa', 'inventarios.sede', 'inventarios.bodega')
        ], 200);

        }

         } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el producto', 'error' => $e->getMessage()], 500);
         }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
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

            $detalleOriginal[] = [
                'producto_id'        => $request->producto_id,
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
        $productoEq = Product::find($eqId);

        $eqResp = [
            'producto_id'      => $eqId,
            'producto_nombre'  => $productoEq?->name ?? "Producto #{$eqId}",
            'razon'            => $razon,
            'bodegas'          => []
        ];

        foreach ($equivalente['bodegas'] as $bodegaEq) {
            $bodegaId  = (int) $bodegaEq['bodega_id'];
            $cantEq    = (float) $bodegaEq['cantidad'];

            // 🔒 Traer TODOS los inventarios de ese equivalente en esa bodega
            $inventariosEq = Inventario::where('producto_id', $eqId)
                ->where('sede_id', $sedeId)
                ->where('bodega_id', $bodegaId)
                ->lockForUpdate()
                ->orderBy('stock', 'DESC')
                ->get();

            if ($inventariosEq->isEmpty()) {
                $eqResp['bodegas'][] = [
                    'bodega_id'     => $bodegaId,
                    'error'         => "No hay inventario disponible en esta bodega para el equivalente",
                ];
                continue;
            }

            $stockTotalEq = (float) $inventariosEq->sum('stock');

            if ($stockTotalEq < $cantEq) {
                $eqResp['bodegas'][] = [
                    'bodega_id'     => $bodegaId,
                    'error'         => "Stock insuficiente en equivalente. Disponible {$stockTotalEq}, Requerido {$cantEq}"
                ];
                continue;
            }

            // ✅ Descontar prorrateado
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
        /*$pdf = Pdf::loadView('pdf.movimiento_stock', [
            'movimiento'       => $movimiento,
            'detalleOriginal'  => $detalleActual['bodegas'],
            'equivalentesResp' => $detalleActual['equivalentes'],
            'errores'          => $detalleActual['errores'],
            'usuario'          => $user,
        ]);

        $fileName = "movimientos/orden_trabajo_{$movimiento->orden_trabajo_id}.pdf";
        Storage::disk('public')->put($fileName, $pdf->output());
        $movimiento->update(['pdf_path' => $fileName]);*/

        // ----------------------------------------------------
        // 🔹 5. Construir respuesta para el frontend
        // ----------------------------------------------------
        $faltante = max(0, $cantidadTotal - $cantidadCubierta);

        return response()->json([
            'success'            => $faltante === 0,
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
        ], $faltante === 0 ? 200 : 400);
    });
}
// Descontar masivamente teniendo en cuenta la estructura de descontar stock
public function descontarStockMasivo(Request $request)
{

    
}


//Obtener el PDF de un movimiento
public function getMovimientoPDF($movimientoId)
{



    $movimiento = MovimientoStock::with('ordenTrabajo.ordenCompra.detalles', 'ordenTrabajo.cliente', 'producto', 'usuario')->find($movimientoId);
    if (!$movimiento) {
        return response()->json(['message' => 'Movimiento no encontrado'], 404);
    }

    if (!$movimiento->pdf_path || !Storage::disk('public')->exists($movimiento->pdf_path)) {
        return response()->json(['message' => 'Archivo PDF no encontrado'], 404);
    }

    return response()->file(storage_path("app/public/{$movimiento->pdf_path}"));
}


public function registrarEntradaStock(RegistrarInventarioResquest $request)
{
    try {
        $user = auth()->user();

        // ✅ VALIDAR que el usuario tenga sede asignada
        if (!$user->sede_id) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene una sede asignada',
                'errors' => ['user' => ['Sede requerida']]
            ], 422);
        }

        $data = $request->validated();
        $data['user_id'] = $user->id;
        $data['sede_id'] = $user->sede_id;

        // ✅ REGISTRAR entrada de stock usando el servicio
        $inventario = $this->productService->registerInventario($data);

        // ✅ CREAR movimiento de stock para trazabilidad
        MovimientoStock::create([
            'producto_id' => $data['producto_id'],
            'usuario_id' => $user->id,
            'tipo' => 'entrada',
            'cantidad' => $data['stock'],
            'detalle' => [
                'bodega_id' => $data['bodega_id'],
                'bodega_nombre' => $inventario->bodega->nombre ?? 'N/A',
                'empresa_id' => $data['empresa_id'],
                'empresa_nombre' => $inventario->empresa->nombre ?? 'N/A',
                'sede_id' => $data['sede_id'],
                'precio' => $data['precio'] ?? null,
                'tipo_operacion' => 'entrada_manual'
            ],
            'razon' => $data['razon'] ?? 'Entrada manual de stock',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Entrada de stock registrada exitosamente',
            'data' => [
                'inventario' => [
                    'id' => $inventario->id,
                    'producto_nombre' => $inventario->producto->name ?? 'N/A',
                    'bodega_nombre' => $inventario->bodega->nombre ?? 'N/A',
                    'empresa_nombre' => $inventario->empresa->nombre ?? 'N/A',
                    'stock_actual' => $inventario->stock,
                    'precio' => $inventario->precio,
                    'fecha_registro' => $inventario->created_at->format('Y-m-d H:i:s')
                ]
            ]
        ], 201);

    } catch (\Exception $e) {
        Log::error('Error al registrar entrada de stock', [
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al registrar entrada de stock: ' . $e->getMessage(),
            'errors' => ['general' => [$e->getMessage()]]
        ], 500);
    }
}
//REGISTRAR INVENTARIO CARGA MASIVA EXEL


public function importarInventarioExcel(StoreExcelProductRequest $request)
{
    try {
        $file = $request->file('file');

        // ✅ Guarda el usuario autenticado
        $user = auth()->user();

        $resultado = $this->productService->importInventarioFromExcel(
            $file,
            $user,
            $request->empresa_id,
            $request->bodega_id
        );
$empresa = empresa::find($request->empresa_id);
$bodega  = bodega::find($request->bodega_id);
        // ✅ 1. Crear movimiento global de stock
        $movimiento = MovimientoStock::create([
            'producto_id' => null,
            'usuario_id' => $user->id,
            'tipo' => 'entrada_masiva',
            'cantidad' => collect($resultado['inventarios'])->sum('stock'),
            'detalle' => [
                'empresa_id' => $request->empresa_id,
                'empresa_nombre' => $empresa->nombre ?? 'N/A',
                'bodega_id' => $request->bodega_id,
                'bodega_nombre' => $bodega->nombre ?? 'N/A',
                'total_registros' => count($resultado['inventarios']),
                'archivo_fuente' => $file->getClientOriginalName(),
                'tipo_operacion' => 'import_excel',
            ],
            'razon' => 'Carga masiva de inventario desde Excel',
        ]);

        // ✅ 2. Generar PDF del resumen
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.movimiento_importacion', [
            'movimiento' => $movimiento,
            'usuario' => $user,
            'inventarios' => $resultado['inventarios'],
            'resumen' => $resultado['resumen'],
            'errores' => $resultado['errores'],
            'fecha' => now()->format('d/m/Y H:i')
        ]);

        $pdfPath = "movimientos/movimiento_{$movimiento->id}.pdf";
        \Illuminate\Support\Facades\Storage::disk('public')->put($pdfPath, $pdf->output());

        $movimiento->update(['pdf_path' => $pdfPath]);

        return response()->json([
            'success' => true,
            'message' => 'Importación completada exitosamente',
            'data' => [
                'resumen' => $resultado['resumen'],
                'errores' => $resultado['errores'],
                'inventarios' => collect($resultado['inventarios'])->map(function ($inv) {
                    return [
                        'id' => $inv->id,
                        'producto_nombre' => $inv->producto->name ?? 'N/A',
                        'bodega_nombre' => $inv->bodega->nombre ?? 'N/A',
                        'empresa_nombre' => $inv->empresa->nombre ?? 'N/A',
                        'stock' => $inv->stock,
                        'actualizado' => $inv->wasRecentlyCreated ? false : true,
                    ];
                }),
                'movimiento_pdf_url' => asset("storage/{$pdfPath}")
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al importar inventarios DESDE Excel: ' . $e->getMessage(),
            'errors' => ['file' => [$e->getMessage()]]
        ], 422);
    }
}


public function importarExcelDescuento(StoreExcelProductRequest $request)
{
    try {
        $file = $request->file('file');
        $user = auth()->user();

        $resultado = $this->productService->descontarInventarioFromExcel(
            $file,
            $user,
            $request->empresa_id,
            $request->bodega_id
        );

        $empresa = empresa::find($request->empresa_id);
        $bodega  = bodega::find($request->bodega_id);

        // Crear movimiento de stock (EGRESO MASIVO)
        $mov = MovimientoStock::create([
            'tipo' => 'descuento_masivo_excel',
            'usuario_id' => $user->id,
            'producto_id' => null,
            'cantidad' => $resultado['resumen']['total_descontado'],
            'detalle' => [
                'empresa' => $empresa->nombre,
                'bodega' => $bodega->nombre,
                'total_lineas' => $resultado['resumen']['total_lineas'],
                'errores' => count($resultado['errores']),
                'archivo_origen' => $file->getClientOriginalName(),
            ],
            'razon' => 'Descuento masivo por carga de Excel',
        ]);

        // Generar PDF
        $pdf = PDF::loadView('pdf.movimiento_descuento_importacion', [
            'movimiento' => $mov,
            'usuario'    => $user,
            'procesados' => $resultado['procesados'],
            'errores'    => $resultado['errores'],
            'fecha'      => now()->format('d/m/Y H:i'),
            'empresa'    => $empresa,
            'bodega'     => $bodega,
        ]);

        $pdfPath = "movimientos/descuento_excel_{$mov->id}.pdf";
        Storage::disk('public')->put($pdfPath, $pdf->output());
        $mov->update(['pdf_path' => $pdfPath]);

        return response()->json([
            'success' => true,
            'message' => 'Descuento masivo completado',
            'pdf_url' => asset("storage/{$pdfPath}"),
            'resumen' => $resultado['resumen'],
            'errores' => $resultado['errores'],
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}

public function stockProductoConSugerencias($productoId, Request $request)
{
     $result = app(\App\Services\ProductService::class)
        ->getStockConSugerencias($productoId, $request->user());

    return response()->json($result);
}

public function sincronizarProductosSiigoGlobal(Request $request)
{
    try {
        $params = $request->all();

        $sincronizados = $this->siigoGlobalService->sincronizarProductosDesdeSiigo($params);

        return response()->json([
            'success' => true,
            'message' => 'Sincronización completada exitosamente',
            'data' => [
                'productos_sincronizados' => $sincronizados,
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al sincronizar productos: ' . $e->getMessage(),
            'errors' => ['general' => [$e->getMessage()]]
        ], 500);
    }

}


public function sincronizarProductosSiigoSetas(Request $request)
{
    try {
        $params = $request->all();

        $sincronizados = $this->siigoService->sincronizarProductosDesdeSiigo($params);

        return response()->json([
            'success' => true,
            'message' => 'Sincronización con Siigo Setas completada exitosamente',
            'data' => [
                'productos_sincronizados' => $sincronizados,
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al sincronizar productos con Siigo Setas: ' . $e->getMessage(),
            'errors' => ['general' => [$e->getMessage()]]
        ], 500);
    }
}

public function exportarPlantillaProductos()
{
    try {
        $fecha = now()->format('Ymd_His');
        $fileName = "plantilla_productos_{$fecha}.xlsx";

        return Excel::download(
            new PlantillaProductosExport,
            $fileName,
            \Maatwebsite\Excel\Excel::XLSX,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]
        );

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al generar la plantilla: ' . $e->getMessage(),
        ], 500);
    }
}

   //Generare  etiquetas codigo de barras PDF
public function barcodesMasivos(Request $request)
{
    $productos = Product::whereIn('id', $request->product_ids)->get();

    // Generar PDF con etiquetas (DomPDF / Snappy)
    return response()->streamDownload(function () use ($productos) {
     ($productos);
        echo PdfEtiquetas::generar($productos);
    }, 'etiquetas.pdf');
}
   
   
   
   


public function testPdf()
{
    $html = '<h1>PDF FUNCIONA</h1><p>Si ves esto, DomPDF está bien</p>';

    return Pdf::loadHTML($html)->stream('test.pdf');
}
}