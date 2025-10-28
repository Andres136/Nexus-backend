<?php

namespace App\Services;

use App\Models\Crm\Inventario;
use App\Models\Crm\product as CrmProduct;
use App\Models\Crm\Sede;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                    'stock'             => $data['stock'],
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

public function getStockConSugerencias(int $productoId, $user): array
    {
        $sedeId = $user->sede_id;

        // 🔹 1. Producto base
        $producto = CrmProduct::findOrFail($productoId);

        // 🔹 2. Inventarios del producto base
        $inventariosBase = Inventario::with('bodega')
            ->where('producto_id', $productoId)
            ->where('sede_id', $sedeId)
            ->get();

        $stockBase = (float) $inventariosBase->sum('stock');

        // 🔹 3. Agrupar y ordenar bodegas del producto base
        $resumenBase = $inventariosBase
            ->groupBy('bodega_id')
            ->map(fn($items) => [
                'bodega_id'     => $items->first()->bodega_id,
                'bodega_nombre' => $items->first()->bodega->nombre ?? 'Sin bodega',
                'stock_total'   => (float) $items->sum('stock'),
            ])
            ->sortByDesc('stock_total')
            ->values();

        // 🔹 4. Detectar números relevantes (dimensiones, calibres, etc.)
        preg_match_all('/\d+(\.\d+)?/', $producto->name . ' ' . $producto->description, $matches);
        $numeros = collect($matches[0])->unique();

        // 🔹 5. Buscar productos similares
        $similares = CrmProduct::where('id', '!=', $productoId)
            ->where(function ($q) use ($producto, $numeros) {
                $q->where('name', 'like', '%' . $producto->name . '%')
                  ->orWhere('description', 'like', '%' . $producto->description . '%')
                  ->orWhere('code', 'like', substr($producto->code, 0, 5) . '%');

                foreach ($numeros as $num) {
                    $q->orWhere('name', 'like', "%{$num}%")
                      ->orWhere('description', 'like', "%{$num}%");
                }
            })
            ->take(25)
            ->get();

        // 🔹 6. Procesar sugerencias
        $sugerencias = $similares->map(function ($p) use ($sedeId) {
            $inv = Inventario::with('bodega')
                ->where('producto_id', $p->id)
                ->where('sede_id', $sedeId)
                ->get();

            $stockTotal = (float) $inv->sum('stock');
            if ($stockTotal <= 0) return null; // ❌ sin stock no interesa

            return [
                'id' => $p->id,
                'nombre' => $p->name,
                'codigo' => $p->code,
                'stock_total' => $stockTotal,
                'disponible' => true,
                'es_base' => false,
                'resumen_por_bodega' => $inv->groupBy('bodega_id')->map(fn($items) => [
                    'bodega_id'     => $items->first()->bodega_id,
                    'bodega_nombre' => $items->first()->bodega->nombre ?? 'Sin bodega',
                    'stock_total'   => (float) $items->sum('stock'),
                ])->sortByDesc('stock_total')->values(),
            ];
        })->filter()->sortByDesc('stock_total')->values();

        // 🔹 7. Si el producto base NO tiene stock → solo sugerencias
        if ($stockBase <= 0) {
            return [
                'producto_base' => [
                    'id' => $producto->id,
                    'nombre' => $producto->name,
                    'codigo' => $producto->code,
                    'stock_total' => 0,
                    'disponible' => false,
                    'es_base' => true,
                    'resumen_por_bodega' => [],
                ],
                'sugerencias' => $sugerencias,
            ];
        }

        // 🔹 8. Si el producto base tiene stock → incluirlo junto con sugerencias
        $productoBase = [
            'id' => $producto->id,
            'nombre' => $producto->name,
            'codigo' => $producto->code,
            'stock_total' => $stockBase,
            'disponible' => $stockBase > 0,
            'es_base' => true,
            'resumen_por_bodega' => $resumenBase,
        ];

        // 🔹 9. Combinar todo (producto base + sugerencias con stock)
        $todosOrdenados = collect([$productoBase])
            ->merge($sugerencias)
            ->sortByDesc(fn($p) => $p['stock_total'])
            ->values();

        return [
            'producto_base' => $productoBase,
            'sugerencias'   => $sugerencias,
            'todos_ordenados' => $todosOrdenados,
        ];
    }


    
    

    
}