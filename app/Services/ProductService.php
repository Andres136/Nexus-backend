<?php

namespace App\Services;

use App\Models\Crm\Inventario;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\product as CrmProduct;
use App\Models\Crm\Sede;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProductService
{
    public function getProducts(Request $request, $user)
    {
        $query = CrmProduct::with([
            'inventarios.empresa',
            'inventarios.sede',
            'inventarios.bodega'
        ]);

        // 🔹 Restringir según rol
        if (!in_array($user->role_id, [1, 2])) {
            $query->whereHas('inventarios', function ($q) use ($user) {
                $q->where('sede_id', $user->sede_id);
            });
        }

        // 🔹 Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // 🔹 Filtros
        if ($request->filled('empresa_id')) {
            $query->whereHas('inventarios', fn($q) => $q->where('empresa_id', $request->empresa_id));
        }

        if ($request->filled('sede_id')) {
            $query->whereHas('inventarios', fn($q) => $q->where('sede_id', $request->sede_id));
        }

        if ($request->filled('bodega_id')) {
            $query->whereHas('inventarios', fn($q) => $q->where('bodega_id', $request->bodega_id));
        }

        // 🔹 Paginación
        $perPage = $request->get('per_page', 10);
        return $query->orderBy('id', 'desc')->paginate($perPage);
    }



public function getStockByProduct($productoId, $user, $bodegaId = null, $sedeId = null)
{
    $query = Inventario::where('producto_id', $productoId)
        ->with(['bodega', 'empresa', 'sede']);

    // Si no se pasa sede explícita, usar la del usuario
    $sedeId = $sedeId ?: $user->sede_id;

    $query->where('sede_id', $sedeId);

    if ($bodegaId) {
        $query->where('bodega_id', $bodegaId);
    }

    $inventarios = $query->get();
    $stockTotal  = $inventarios->sum('stock');

    return [
        'producto_id' => (string) $productoId,
        'stock_total' => $stockTotal,
        'inventarios' => $inventarios->map(function ($inv) {
            return [
                'id'                => $inv->id,
                'bodega_id'         => $inv->bodega_id,
                'bodega_nombre'     => $inv->bodega->nombre ?? 'Sin bodega',
                'empresa_id'        => $inv->empresa_id,
                'empresa_nombre'    => $inv->empresa->nombre ?? 'Sin empresa',
                'sede_id'           => $inv->sede_id,
                'sede_nombre'       => $inv->sede->nombre ?? 'Sin sede',
                'stock'             => $inv->stock,
                'precio'            => $inv->precio,
                'min_stock'         => $inv->min_stock,
                'max_stock'         => $inv->max_stock,
                'fecha_vencimiento' => $inv->fecha_vencimiento,
                'created_at'        => $inv->created_at,
                'updated_at'        => $inv->updated_at,
            ];
        }),
        'resumen_por_bodega' => $inventarios->groupBy('bodega_id')->map(function ($items, $bodegaId) {
            $firstItem = $items->first();
            return [
                'bodega_id'         => $bodegaId,
                'bodega_nombre'     => $firstItem->bodega->nombre ?? 'Sin bodega',
                'stock_total'       => $items->sum('stock'),
                'cantidad_registros'=> $items->count(),
            ];
        })->values(),
    ];
}

public function getStockForUserAndOrder($productoId, $user, $orderSedeId, $bodegaId = null)
{
    // Stock en sede autenticada
    $stockUser = Inventario::where('producto_id', $productoId)
        ->where('sede_id', $user->sede_id)
        ->with(['bodega', 'empresa', 'sede'])
        ->get();

    $stockUserData = [
        'producto_id' => $productoId,
        'sede_id'     => $user->sede_id,
        'sede_nombre' => $stockUser->first()?->sede->nombre ?? 'Sin sede',
        'stock_total' => $stockUser->sum('stock'),
        'resumen_por_bodega' => $stockUser->groupBy('bodega_id')->map(function ($items, $bodegaId) {
            $firstItem = $items->first();
            return [
                'bodega_id'        => $bodegaId,
                'bodega_nombre'    => $firstItem->bodega->nombre ?? 'Sin bodega',
                'stock_total'      => $items->sum('stock'),
                'cantidad_registros' => $items->count(),
            ];
        })->values(),
    ];

    // Stock en sede de la orden (si es distinta a la autenticada)
    $stockOrder = null;
    if ($orderSedeId && $orderSedeId != $user->sede_id) {
        $stockOrderQuery = Inventario::where('producto_id', $productoId)
            ->where('sede_id', $orderSedeId)
            ->with(['bodega', 'empresa', 'sede'])
            ->get();

        $stockOrder = [
            'producto_id' => $productoId,
            'sede_id'     => $orderSedeId,
            'sede_nombre' => Sede::find($orderSedeId)->nombre ?? 'Sin sede',
            'stock_total' => $stockOrderQuery->sum('stock'),
            'resumen_por_bodega' => $stockOrderQuery->groupBy('bodega_id')->map(function ($items, $bodegaId) {
                $firstItem = $items->first();
                return [
                    'bodega_id'        => $bodegaId,
                    'bodega_nombre'    => $firstItem->bodega->nombre ?? 'Sin bodega',
                    'stock_total'      => $items->sum('stock'),
                    'cantidad_registros' => $items->count(),
                ];
            })->values(),
        ];
    }

    return [
        'user_sede'  => $stockUserData,
        'order_sede' => $stockOrder,
    ];
}



public function registerInventario(array $data)
{
    return DB::transaction(function () use ($data) {
        $inventario = Inventario::firstOrNew([
            'producto_id' => $data['producto_id'],
            'empresa_id'  => $data['empresa_id'],
            'bodega_id'   => $data['bodega_id'],
            'sede_id'     => $data['sede_id'],
        ]);

        $inventario->stock = ($inventario->exists)
            ? $inventario->stock + ($data['stock'] ?? 0)
            : ($data['stock'] ?? 0);

        // Actualizar solo los campos enviados
        foreach (['precio', 'min_stock', 'max_stock', 'fecha_vencimiento'] as $campo) {
            if (array_key_exists($campo, $data)) {
                $inventario->$campo = $data[$campo] ?? ($inventario->$campo ?? 0);
            }
        }

        $inventario->user_id = $data['user_id'] ?? $inventario->user_id;
        $inventario->save();

        return $inventario;
    });
}



public function importInventarioFromExcel($filePath, $user, $empresaId, $bodegaId)
{
    $rows = \Maatwebsite\Excel\Facades\Excel::toArray([], $filePath)[0];
    $header = array_map('strtolower', array_shift($rows));

    // ✅ Columnas requeridas
    $requiredColumns = ['code', 'stock'];
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $header)) {
            throw new \Exception("Columna requerida faltante: {$col}");
        }
    }

    $inventarios = [];
    $actualizados = 0;
    $creados = 0;
    $errores = [];

    foreach ($rows as $index => $row) {
        try {
            $data = array_combine($header, $row);

            // ✅ Datos base del usuario y frontend
            $data['empresa_id'] = $empresaId;
            $data['bodega_id'] = $bodegaId;
            $data['sede_id'] = $user->sede_id;
            $data['usuario_id'] = $user->id;

            // ✅ Validaciones generales
            foreach ($requiredColumns as $col) {
                if (!isset($data[$col]) || trim($data[$col]) === '') {
                    throw new \Exception("Dato requerido faltante en columna '{$col}'");
                }
            }

            if (!$user->sede_id) throw new \Exception("El usuario no tiene una sede asignada");
            if (!$empresaId) throw new \Exception("Debe seleccionar una empresa");
            if (!$bodegaId) throw new \Exception("Debe seleccionar una bodega");

            // ✅ Buscar producto
            $producto = CrmProduct::where('code', $data['code'])->first();
            if (!$producto) {
                throw new \Exception("Producto con código '{$data['code']}' no encontrado");
            }
            $data['producto_id'] = $producto->id;

            // ✅ Normalizar y validar el campo de stock (corrige el problema de las comas)
            $rawStock = $data['stock'];
            $data['stock'] = str_replace(',', '.', trim($rawStock));

            if (!is_numeric($data['stock'])) {
                throw new \Exception("El valor '{$rawStock}' no es numérico o tiene formato inválido");
            }

            $data['stock'] = (float) $data['stock']; // conversión segura a número decimal

            // ✅ Buscar inventario existente
            $inventarioExistente = Inventario::where([
                'producto_id' => $data['producto_id'],
                'empresa_id'  => $data['empresa_id'],
                'sede_id'     => $data['sede_id'],
                'bodega_id'   => $data['bodega_id'],
            ])->first();

            if ($inventarioExistente) {
                // 🔄 Actualizar
         $inventarioExistente->update([
    'stock'             => $inventarioExistente->stock + $data['stock'],
    'precio'            => $data['precio'] ?? $inventarioExistente->precio,
    'min_stock'         => $data['min_stock'] ?? $inventarioExistente->min_stock,
    'max_stock'         => $data['max_stock'] ?? $inventarioExistente->max_stock,
    'fecha_vencimiento' => $data['fecha_vencimiento'] ?? $inventarioExistente->fecha_vencimiento,
    'updated_at'        => now(),
]);

                $inventarios[] = $inventarioExistente;
                $actualizados++;
            } else {
                //  Crear nuevo inventario
                $inventario = $this->registerInventario($data);
                $inventarios[] = $inventario;
                $creados++;
            }

        } catch (\Exception $e) {
            $errores[] = [
                'fila' => $index + 2, // +2 por encabezado
                'error' => $e->getMessage(),
                'valor_stock' => $row[array_search('stock', $header)] ?? null,
                'codigo' => $row[array_search('code', $header)] ?? null,
            ];
        }
    }

    return [
        'inventarios' => $inventarios,
        'resumen' => [
            'total_procesado' => count($rows),
            'creados' => $creados,
            'actualizados' => $actualizados,
            'errores' => count($errores),
            'sede_utilizada' => $user->sede_id,
            'empresa_utilizada' => $empresaId,
            'bodega_utilizada' => $bodegaId,
        ],
        'errores' => $errores,
    ];
}


/**
 * Descontar stock de inventario via exel
 * 

 */

public function descontarInventarioFromExcel($filePath, $user, $empresaId, $bodegaId)
{
    $rows = \Maatwebsite\Excel\Facades\Excel::toArray([], $filePath)[0];
    $header = array_map('strtolower', array_shift($rows));

    // Columnas requeridas
    $requiredColumns = ['code', 'stock'];

    foreach ($requiredColumns as $col) {
        if (!in_array($col, $header)) {
            throw new \Exception("Columna requerida faltante: {$col}");
        }
    }

    $procesados = [];
    $errores = [];
    $totalDescontado = 0;

    DB::beginTransaction();

    try {

        foreach ($rows as $index => $row) {
            try {
                $data = array_combine($header, $row);

                // Validar campos obligatorios
                foreach ($requiredColumns as $col) {
                    if (!isset($data[$col]) || trim($data[$col]) === '') {
                        throw new \Exception("Dato requerido faltante en columna '{$col}'");
                    }
                }

                if (!$user->sede_id) throw new \Exception("El usuario no tiene una sede asignada");
                if (!$empresaId) throw new \Exception("Debe seleccionar una empresa");
                if (!$bodegaId) throw new \Exception("Debe seleccionar una bodega");

                // Buscar producto por código
                $producto = CrmProduct::where('code', $data['code'])->first();
                if (!$producto) {
                    throw new \Exception("Producto con código '{$data['code']}' no encontrado");
                }

                $data['producto_id'] = $producto->id;

                // Normalizar stock
                $rawStock = $data['stock'];
                $data['stock'] = (float) str_replace(',', '.', trim($rawStock));

                if (!is_numeric($data['stock'])) {
                    throw new \Exception("El valor '{$rawStock}' no es numérico o tiene formato inválido");
                }

                $cantidadADescontar = $data['stock'];

                // Buscar inventario
                $inventario = Inventario::where([
                    'producto_id' => $data['producto_id'],
                    'empresa_id'  => $empresaId,
                    'sede_id'     => $user->sede_id,
                    'bodega_id'   => $bodegaId,
                ])
                ->lockForUpdate()
                ->first();

                if (!$inventario) {
                    throw new \Exception("No existe inventario para este producto en esta bodega");
                }

                // Validar stock suficiente
                if ($inventario->stock < $cantidadADescontar) {
                    throw new \Exception(
                        "Stock insuficiente. Disponible {$inventario->stock}, requerido {$cantidadADescontar}"
                    );
                }

                // Guardar detalle antes
                $stockAntes = $inventario->stock;

                // Descontar
                $inventario->decrement('stock', $cantidadADescontar);

                // Acumular resultados
                $procesados[] = [
                    'producto_id'       => $producto->id,
                    'producto_nombre'   => $producto->name,
                    'codigo'            => $producto->code,
                    'cantidad_descontada' => $cantidadADescontar,
                    'stock_antes'       => $stockAntes,
                    'stock_despues'     => $inventario->stock,
                    'bodega_id'         => $bodegaId,
                    'empresa_id'        => $empresaId,
                ];

                $totalDescontado += $cantidadADescontar;

            } catch (\Exception $e) {
                $errores[] = [
                    'fila' => $index + 2,
                    'error' => $e->getMessage(),
                    'code' => $row[array_search('code', $header)] ?? null,
                    'valor_stock' => $row[array_search('stock', $header)] ?? null,
                ];
            }
        }

        DB::commit();

        return [
            'procesados' => $procesados,
            'resumen' => [
                'total_lineas' => count($rows),
                'total_descontado' => $totalDescontado,
                'errores' => count($errores),
                'empresa_id' => $empresaId,
                'bodega_id'  => $bodegaId,
                'sede_id'    => $user->sede_id,
            ],
            'errores' => $errores,
        ];

    } catch (\Throwable $e) {
        DB::rollBack();
        throw $e;
    }
}


public function getStockConSugerencias(int $productoId, $user): array
{
    $sedeId = $user->sede_id;

    // 1️⃣ Producto base
    $producto = CrmProduct::findOrFail($productoId);

    // 2️⃣ Inventarios del producto base
    $inventariosBase = Inventario::with('bodega')
        ->where('producto_id', $productoId)
        ->where('sede_id', $sedeId)
        ->get();

    $stockBase = (float) $inventariosBase->sum('stock');

    $resumenBase = $inventariosBase
        ->groupBy('bodega_id')
        ->map(fn($items) => [
            'bodega_id'     => $items->first()->bodega_id,
            'bodega_nombre' => $items->first()->bodega->nombre ?? 'Sin bodega',
            'stock_total'   => (float) $items->sum('stock'),
        ])
        ->sortByDesc('stock_total')
        ->values();

    // 3️⃣ Extraer tokens significativos del nombre y descripción
    $textoBase = strtoupper($producto->name . ' ' . $producto->description);
    $tokens = collect(explode(' ', preg_replace('/[^A-Za-z0-9\.]/', ' ', $textoBase)))
        ->filter(fn($t) => strlen($t) > 2)
        ->values();

    // 4️⃣ Generar patrón de similitud
    $patron = $tokens->map(fn($t) => "%$t%");

    // 5️⃣ Buscar productos similares con coincidencia fuerte
    $similares = CrmProduct::where('id', '!=', $productoId)
        ->where(function ($q) use ($producto, $tokens, $patron) {
            // coincidencia exacta parcial entre nombre o descripción
            foreach ($patron as $p) {
                $q->orWhere('name', 'like', $p)
                  ->orWhere('description', 'like', $p);
            }
            // coincidencia por prefijo de código
            if (!empty($producto->code)) {
                $q->orWhere('code', 'like', substr($producto->code, 0, 5) . '%');
            }
        })
        ->take(30)
        ->get();

    // 6️⃣ Calcular nivel de similitud (ratio)
    $similaresFiltrados = $similares->map(function ($p) use ($textoBase, $sedeId) {
        $textoSim = strtoupper($p->name . ' ' . $p->description);
        similar_text($textoBase, $textoSim, $porcentaje);

        $inv = Inventario::with('bodega')
            ->where('producto_id', $p->id)
            ->where('sede_id', $sedeId)
            ->get();

        $stockTotal = (float) $inv->sum('stock');
        if ($stockTotal <= 0) return null;

        return [
            'id' => $p->id,
            'nombre' => $p->name,
            'codigo' => $p->code,
            'stock_total' => $stockTotal,
            'similitud' => round($porcentaje, 2),
            'disponible' => true,
            'es_base' => false,
            'resumen_por_bodega' => $inv->groupBy('bodega_id')->map(fn($items) => [
                'bodega_id'     => $items->first()->bodega_id,
                'bodega_nombre' => $items->first()->bodega->nombre ?? 'Sin bodega',
                'stock_total'   => (float) $items->sum('stock'),
            ])->sortByDesc('stock_total')->values(),
        ];
    })
    ->filter(fn($item) => $item && $item['similitud'] >= 55) // ⚙️ solo productos con ≥55% de similitud
    ->sortByDesc('similitud')
    ->values();

    // 7️⃣ Producto base
    $productoBase = [
        'id' => $producto->id,
        'nombre' => $producto->name,
        'codigo' => $producto->code,
        'stock_total' => $stockBase,
        'disponible' => $stockBase > 0,
        'es_base' => true,
        'resumen_por_bodega' => $resumenBase,
    ];

    // 8️⃣ Combinar
    $todosOrdenados = collect([$productoBase])
        ->merge($similaresFiltrados)
        ->sortByDesc(fn($p) => $p['stock_total'])
        ->values();

    return [
        'producto_base' => $productoBase,
        'sugerencias'   => $similaresFiltrados,
        'todos_ordenados' => $todosOrdenados,
    ];
}




public function getFaltantesOrdenesPendientes()
{
    $user = auth()->user();

    $ordenes = Orden_Compra::with(['detalles.product', 'estado', 'cliente','OrdenesTrabajo'])
        ->whereIn('estado_id', [1, 5]) // Pendiente y Parcial
        ->when(!in_array($user->role_id, [1, 2, 4]), function ($query) use ($user) {
            // 🔒 Si no es admin, superadmin o gerente, filtra por la sede del usuario
            $query->where('sede_id', $user->sede_id);
        })
        ->get();

    $resultado = [];

    foreach ($ordenes as $orden) {
        $faltantes = [];

        foreach ($orden->detalles as $detalle) {
            if (!$detalle->product) continue;

            $productoId = $detalle->product_id;
            $cantidadRequerida = $detalle->cantidad_requerida_kg ?? 0;

            $stockInfo = $this->getStockByProduct($productoId, $user);
            $stockTotal = $stockInfo['stock_total'];
            $faltante = max(0, $cantidadRequerida - $stockTotal);

            if ($faltante > 0) {
                $faltantes[] = [
                    'producto_id'        => $productoId,
                    'codigo'             => $detalle->product->code ?? '-',
                    'nombre'             => $detalle->product->name ?? 'Sin nombre',
                    'cantidad_requerida' => floatval($cantidadRequerida),
                    'stock_disponible'   => floatval($stockTotal),
                    'faltante'           => floatval($faltante),
                    'resumen_bodegas'    => $stockInfo['resumen_por_bodega'],
                ];
            }
        }

        if (!empty($faltantes)) {
            $resultado[] = [
                'orden_id'        => $orden->id,
                'codigo'          => "OC-" . str_pad($orden->id, 4, '0', STR_PAD_LEFT),
                'estado'          => $orden->estado->nombre ?? 'Desconocido',
                'cliente'         => [
                    'id'      => $orden->cliente->id ?? null,
                    'nombre'  => $orden->cliente->nombre ?? 'Sin cliente',
                    'nit'     => $orden->cliente->nit ?? null,
                ],
                'sede'            => [
                    'id'      => $orden->sede->id ?? null,
                    'nombre'  => $orden->sede->nombre ?? 'Sin sede',
                ],
                'ordenes_trabajo' => $orden->OrdenesTrabajo->map(function ($ot) {
                    return [
                        'id'      => $ot->id,
                        'codigo'  => "OT-" . str_pad($ot->id, 4, '0', STR_PAD_LEFT),
                        'estado'  => $ot->estado->nombre ?? 'Desconocido',
                    ];
                }),
                'faltantes_total' => count($faltantes),
                'faltantes'       => $faltantes,
            ];  
        }
    }

    return [
        'total_ordenes' => count($resultado),
        'ordenes'       => $resultado,
    ];
}




    

    
}