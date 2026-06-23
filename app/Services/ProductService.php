<?php

namespace App\Services;

use App\EstadoEnum;
use App\Models\Crm\Inventario;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\Orden_servicio\OrdenServicioDetalle;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use App\Models\Crm\product as CrmProduct;
use App\Models\Crm\Sede;

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
                    'cantidad_registros' => $items->count(),
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
        $header = array_map(
            fn($h) => trim(strtolower($h)),
            array_shift($rows)
        );


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
                // 🔧 Normalizar valores (elimina espacios invisibles de Excel)
                foreach ($data as $key => $value) {
                    if (is_string($value)) {
                        $value = trim($value);                       // espacios visibles
                        $value = preg_replace('/\s+/u', ' ', $value); // tabs, saltos de línea
                        $value = preg_replace('/[^\PC\s]/u', '', $value); // caracteres invisibles
                        $data[$key] = $value;
                    }
                }

                // ✅ Datos base del usuario y frontend
                $data['empresa_id'] = $empresaId;
                $data['bodega_id'] = $bodegaId;
                $data['sede_id'] = $user->sede_id;
                $data['usuario_id'] = $user->id;
                //  Saltar filas completamente vacías
                if (empty(array_filter($data, fn($v) => trim((string)$v) !== ''))) {
                    continue;
                }

                // ✅ Validaciones generales
                foreach ($requiredColumns as $col) {
                }
                if (!array_key_exists($col, $data) || $data[$col] === '') {
                    throw new \Exception("Dato requerido faltante o inválido en columna '{$col}'");
                }


                if (!$user->sede_id) throw new \Exception("El usuario no tiene una sede asignada");
                if (!$empresaId) throw new \Exception("Debe seleccionar una empresa");
                if (!$bodegaId) throw new \Exception("Debe seleccionar una bodega");
                // 🔒 Normalizar código definitivamente
                $data['code'] = (string) $data['code'];
                $data['code'] = trim($data['code']);
                $data['code'] = preg_replace('/\.0$/', '', $data['code']); // Excel numérico

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
                //Procesar fecha de vencimiento si existe
                if (isset($data['fecha_vencimiento']) && !empty($data['fecha_vencimiento'])) {
                    $data['fecha_vencimiento'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data['fecha_vencimiento'])->format('Y-m-d');
                }


                $data['stock'] = (float) $data['stock']; // conversión segura a número decimal
                if ($data['stock'] <= 0) throw new \Exception(
                    "Producto con código '{$data['code']}' no encontrado (verifique espacios o formato)"
                );



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
        $header = array_map(
            fn($h) => trim(strtolower($h)),
            array_shift($rows)
        );


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

                    // 🔧 Normalizar datos (Excel introduce basura invisible)
                    foreach ($data as $key => $value) {
                        if (is_string($value)) {
                            $value = trim($value);
                            $value = preg_replace('/\s+/u', ' ', $value);          // tabs, saltos
                            $value = preg_replace('/[^\PC\s]/u', '', $value);     // invisibles
                            $data[$key] = $value;
                        }
                    }
                    //  Ignorar filas completamente vacías
                    if (empty(array_filter($data, fn($v) => trim((string)$v) !== ''))) {
                        continue;
                    }


                    // Validar campos obligatorios
                    foreach ($requiredColumns as $col) {
                        if (!array_key_exists($col, $data) || $data[$col] === '') {
                            throw new \Exception("Dato requerido faltante o inválido en columna '{$col}'");
                        }
                    }

                    if (!$user->sede_id) throw new \Exception("El usuario no tiene una sede asignada");
                    if (!$empresaId) throw new \Exception("Debe seleccionar una empresa");
                    if (!$bodegaId) throw new \Exception("Debe seleccionar una bodega");

                    // 🔒 Normalizar código (Excel numérico / espacios)
                    $data['code'] = (string) $data['code'];
                    $data['code'] = trim($data['code']);
                    $data['code'] = preg_replace('/\.0$/', '', $data['code']);


                    // Buscar producto por código

                    $producto = CrmProduct::where('code', $data['code'])->first();
                    if (!$producto) {
                        throw new \Exception("Producto con código '{$data['code']}' no encontrado");
                    }

                    $data['producto_id'] = $producto->id;

                    // Normalizar stock
                    $rawStock = (string) $data['stock'];
                    $rawStock = trim($rawStock);
                    $rawStock = str_replace(',', '.', $rawStock);

                    if (!is_numeric($rawStock)) {
                        throw new \Exception("El valor '{$rawStock}' no es numérico o tiene formato inválido");
                    }

                    $cantidadADescontar = (float) $rawStock;

                    if ($cantidadADescontar <= 0) {
                        throw new \Exception("La cantidad a descontar debe ser mayor a 0");
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
                        throw new \Exception(
                            "No existe inventario para el producto '{$producto->code}' en esta sede y bodega"
                        );
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



public function getStockConSugerencias(int $productoId, $user, $request = null): array
{
    // 1️⃣ Sede
    $sedeId = $request && $request->filled('sede_id') ? $request->input('sede_id') : $user->sede_id;
    $search = $request && $request->filled('search') ? $request->input('search') : null;

    // 2️⃣ Producto base
    $producto = CrmProduct::findOrFail($productoId);

    // 3️⃣ Inventarios del producto base
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

    // 4️⃣ Sugerencias tipo search
    if ($search) {
        $similares = CrmProduct::where('id', '!=', $productoId)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%")
                  ->orWhere('code', 'like', "%$search%");
            })
            ->take(30)
            ->get();
    } else {
        // Sugerencias por similitud
        $textoBase = strtoupper($producto->name . ' ' . $producto->description);
        $tokens = collect(explode(' ', preg_replace('/[^A-Za-z0-9\.]/', ' ', $textoBase)))
            ->filter(fn($t) => strlen($t) > 2)
            ->values();
        $patron = $tokens->map(fn($t) => "%$t%");
        $similares = CrmProduct::where('id', '!=', $productoId)
            ->where(function ($q) use ($producto, $tokens, $patron) {
                foreach ($patron as $p) {
                    $q->orWhere('name', 'like', $p)
                        ->orWhere('description', 'like', $p);
                }
                if (!empty($producto->code)) {
                    $q->orWhere('code', 'like', substr($producto->code, 0, 5) . '%');
                }
            })
            ->take(30)
            ->get();
    }

    // 5️⃣ Calcular nivel de similitud y stock
    $similaresFiltrados = $similares->map(function ($p) use ($producto, $sedeId, $search) {
        $inv = Inventario::with('bodega')
            ->where('producto_id', $p->id)
            ->where('sede_id', $sedeId)
            ->get();
        $stockTotal = (float) $inv->sum('stock');
        if ($stockTotal <= 0) return null;

        $similitud = 0;
        if (!$search) {
            $textoBase = strtoupper($producto->name . ' ' . $producto->description);
            $textoSim = strtoupper($p->name . ' ' . $p->description);
            similar_text($textoBase, $textoSim, $similitud);
        }

        return [
            'id' => $p->id,
            'nombre' => $p->name,
            'codigo' => $p->code,
            'stock_total' => $stockTotal,
            'similitud' => $similitud ? round($similitud, 2) : null,
            'disponible' => true,
            'es_base' => false,
            'resumen_por_bodega' => $inv->groupBy('bodega_id')->map(fn($items) => [
                'bodega_id'     => $items->first()->bodega_id,
                'bodega_nombre' => $items->first()->bodega->nombre ?? 'Sin bodega',
                'stock_total'   => (float) $items->sum('stock'),
            ])->sortByDesc('stock_total')->values(),
        ];
    })
    ->filter(fn($item) => $item && ($search || (!$search && $item['similitud'] >= 55)))
    ->sortByDesc($search ? 'stock_total' : 'similitud')
    ->values();

    // 6️⃣ Producto base
    $productoBase = [
        'id' => $producto->id,
        'nombre' => $producto->name,
        'codigo' => $producto->code,
        'stock_total' => $stockBase,
        'disponible' => $stockBase > 0,
        'es_base' => true,
        'resumen_por_bodega' => $resumenBase,
    ];

    // 7️⃣ Combinar
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


    /*
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
}*/



    public function getFaltantesOrdenesPendientes(Request $request)
    {
        $user = auth()->user();

        $search = trim((string) $request->input('search', ''));
        $estado = trim((string) $request->input('estado', ''));
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $bodegaId = $request->filled('bodega_id') ? (int) $request->input('bodega_id') : null;
        $sedeIdFiltro = $request->filled('sede_id')
            ? (int) $request->sede_id
            : null;

        $ordenes = Orden_Compra::with([
            'detalles.product',
            'estado',
            'cliente',
            'sede',
            'ordenesTrabajo.estado',
        ])
            ->whereIn('estado_id', [
                EstadoEnum::PENDIENTE->value,
                EstadoEnum::ENTREGA_PARCIAL->value,
            ])
            ->whereHas('detalles', fn($query) => $query
                ->whereNotNull('product_id')
                ->where('cantidad_requerida_kg', '>', 0))
            ->when($sedeIdFiltro, fn($query) => $query->where('sede_id', $sedeIdFiltro))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhereHas('cliente', function ($clienteQuery) use ($search) {
                            $clienteQuery->where('nombre', 'like', "%{$search}%");
                        });
                });
            })
            ->when($estado, function ($query) use ($estado) {
                $query->whereHas('estado', function ($q) use ($estado) {
                    $q->where('nombre', $estado);
                });
            })
            ->orderBy('fecha_entrega')
            ->orderBy('id')
            ->get();

        $resultado = [];

        foreach ($ordenes as $orden) {
            $faltantes = [];

            foreach ($orden->detalles as $detalle) {
                if (! $detalle->product) {
                    continue;
                }

                $productoId = $detalle->product_id;
                $cantidadRequeridaKg = (float) ($detalle->cantidad_requerida_kg ?? 0);
                $cantidadPendienteKg = $this->calcularCantidadPendienteKg($detalle);

                if ($cantidadPendienteKg <= 0) {
                    continue;
                }

                $sedeStockId = $sedeIdFiltro ?: ($orden->sede_id ?: $user->sede_id);
                $stockInfo = $this->getStockByProduct($productoId, $user, $bodegaId, $sedeStockId);
                $stockTotal = (float) $stockInfo['stock_total'];
                $faltanteStock = round(max(0, $cantidadPendienteKg - $stockTotal), 2);

                if ($faltanteStock <= 0) {
                    continue;
                }

                $ordenesProveedor = OrdenCompraProveedorDetalle::with(['orden.proveedor'])
                    ->where('producto_id', $productoId)
                    ->whereRaw('COALESCE(cantidad_entregada, 0) < cantidad_solicitada')
                    ->whereHas('orden', function ($query) use ($sedeStockId, $bodegaId) {
                        $query->whereIn('estado_id', [
                            EstadoEnum::PENDIENTE->value,
                            EstadoEnum::ENTREGA_PARCIAL->value,
                        ])
                            ->where('sede_id', $sedeStockId)
                            ->when($bodegaId, fn($bodega) => $bodega->where('bodega_id', $bodegaId));
                    })
                    ->get();

                $pendienteProveedor = round((float) $ordenesProveedor->sum(
                    fn($detalleProveedor) => max(
                        0,
                        (float) $detalleProveedor->cantidad_solicitada
                        - (float) $detalleProveedor->cantidad_entregada
                    )
                ), 2);
                $disponibleTotal = round($stockTotal + $pendienteProveedor, 2);
                $faltanteReal = round(max(0, $cantidadPendienteKg - $disponibleTotal), 2);

                $ordenesServicio = OrdenServicioDetalle::with([
                    'ordenServicio.proveedor',
                    'ordenCompraDetalle.producto',
                ])
                    ->whereHas('ordenCompraDetalle', function ($query) use ($productoId, $sedeStockId) {
                        $query->where('producto_id', $productoId)
                            ->whereHas('orden', fn($ordenProveedor) => $ordenProveedor
                                ->where('sede_id', $sedeStockId));
                    })
                    ->whereHas('ordenServicio', fn($query) => $query
                        ->whereIn('estado', ['pendiente', 'en_proceso']))
                    ->get();

                $enProduccion = round((float) $ordenesServicio->sum('cantidad'), 2);

                $faltantes[] = [
                    'producto_id' => $productoId,
                    'codigo' => $detalle->product->code ?? '-',
                    'nombre' => $detalle->product->name ?? 'Sin nombre',
                    'cantidad_requerida' => $cantidadPendienteKg,
                    'cantidad_requerida_original_kg' => $cantidadRequeridaKg,
                    'cantidad_enviada_kg' => round($cantidadRequeridaKg - $cantidadPendienteKg, 2),
                    'stock_disponible' => round($stockTotal, 2),
                    'faltante' => $faltanteStock,
                    'disponible_total' => $disponibleTotal,
                    'faltante_real' => $faltanteReal,
                    'proveedor_cubre_necesidad' => $faltanteReal <= 0,
                    'solicitado_proveedor' => $pendienteProveedor,
                    'en_produccion' => $enProduccion,
                    'ordenes_proveedor' => $ordenesProveedor->map(fn($op) => [
                        'id' => $op->orden?->id,
                        'codigo' => $op->orden
                            ? 'OP-'.str_pad($op->orden->id, 4, '0', STR_PAD_LEFT)
                            : null,
                        'estado' => $op->orden?->estado,
                        'pendiente' => round(max(
                            0,
                            (float) $op->cantidad_solicitada - (float) $op->cantidad_entregada
                        ), 2),
                        'proveedor' => [
                            'id' => $op->orden?->proveedor?->id,
                            'nombre' => $op->orden?->proveedor?->nombre,
                        ],
                    ])->values(),
                    'ordenes_servicio' => $ordenesServicio->map(fn($os) => [
                        'id' => $os->ordenServicio?->id,
                        'codigo' => $os->ordenServicio
                            ? 'OS-'.str_pad($os->ordenServicio->id, 4, '0', STR_PAD_LEFT)
                            : null,
                        'estado' => $os->ordenServicio?->estado,
                        'proveedor' => [
                            'id' => $os->ordenServicio?->proveedor?->id,
                            'nombre' => $os->ordenServicio?->proveedor?->nombre,
                        ],
                    ])->values(),
                    'resumen_bodegas' => $stockInfo['resumen_por_bodega'],
                ];
            }

            if ($faltantes !== []) {
                $resultado[] = [
                    'orden_id' => $orden->id,
                    'fecha_entrega' => $orden->fecha_entrega,
                    'codigo' => 'OC-'.str_pad($orden->id, 4, '0', STR_PAD_LEFT),
                    'estado' => $orden->estado->nombre ?? 'Desconocido',
                    'cliente' => [
                        'id' => $orden->cliente->id ?? null,
                        'nombre' => $orden->cliente->nombre ?? 'Sin cliente',
                        'nit' => $orden->cliente->nit ?? null,
                    ],
                    'sede' => [
                        'id' => $orden->sede->id ?? null,
                        'nombre' => $orden->sede->nombre ?? 'Sin sede',
                    ],
                    'ordenes_trabajo' => $orden->ordenesTrabajo->map(fn($ot) => [
                        'id' => $ot->id,
                        'codigo' => 'OT-'.str_pad($ot->id, 4, '0', STR_PAD_LEFT),
                        'estado' => $ot->estado->nombre ?? 'Desconocido',
                    ])->values(),
                    'faltantes_total' => count($faltantes),
                    'faltantes_kg' => round((float) collect($faltantes)->sum('faltante'), 2),
                    'faltantes' => $faltantes,
                ];
            }
        }

        $total = count($resultado);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $currentPage = min(max((int) $request->input('page', 1), 1), $lastPage);
        $offset = ($currentPage - 1) * $perPage;

        return [
            'data' => array_values(array_slice($resultado, $offset, $perPage)),
            'pagination' => [
                'total' => $total,
                'per_page' => (int) $perPage,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
            ],
        ];
    }

    public function getEstadisticasFaltantes(Request $request)
    {
        $user = auth()->user();
        $sedeIdFiltro = $request->filled('sede_id')
            ? (int) $request->sede_id
            : null;
        $bodegaId = $request->filled('bodega_id') ? (int) $request->bodega_id : null;

        // Órdenes pendientes/parciales con sus detalles
        $ordenes = Orden_Compra::with(['detalles' => fn($q) => $q
            ->whereNotNull('product_id')
            ->where('cantidad_requerida_kg', '>', 0)])
            ->whereIn('estado_id', [
                EstadoEnum::PENDIENTE->value,
                EstadoEnum::ENTREGA_PARCIAL->value,
            ])
            ->when($sedeIdFiltro, fn($q) => $q->where('sede_id', $sedeIdFiltro))
            ->get();

        $productoIds = $ordenes->flatMap(fn($o) => $o->detalles->pluck('product_id'))->unique()->values();

        if ($productoIds->isEmpty()) {
            return response()->json([
                'total_ordenes_faltantes'    => 0,
                'total_referencias_faltantes' => 0,
                'sin_cobertura'              => 0,
                'cubiertos_proveedor'        => 0,
                'stock_total_sede'           => 0,
            ]);
        }

        // Stock en bulk (una sola query)
        $stocks = Inventario::whereIn('producto_id', $productoIds)
            ->when($sedeIdFiltro, fn($q) => $q->where('sede_id', $sedeIdFiltro))
            ->when($bodegaId, fn($q) => $q->where('bodega_id', $bodegaId))
            ->groupBy('producto_id', 'sede_id')
            ->selectRaw('producto_id, sede_id, SUM(stock) as stock_total')
            ->get()
            ->keyBy(fn($stock) => "{$stock->sede_id}:{$stock->producto_id}");

        // OC proveedor pendientes en bulk (una sola query)
        $enProveedor = OrdenCompraProveedorDetalle::query()
            ->join('orden_compra_proveedores as orden_proveedor', 'orden_proveedor.id', '=', 'orden_compra_proveedor_detalles.orden_id')
            ->whereIn('orden_compra_proveedor_detalles.producto_id', $productoIds)
            ->whereIn('orden_proveedor.estado_id', [
                EstadoEnum::PENDIENTE->value,
                EstadoEnum::ENTREGA_PARCIAL->value,
            ])
            ->when($sedeIdFiltro, fn($query) => $query->where('orden_proveedor.sede_id', $sedeIdFiltro))
            ->when($bodegaId, fn($query) => $query->where('orden_proveedor.bodega_id', $bodegaId))
            ->groupBy('orden_compra_proveedor_detalles.producto_id', 'orden_proveedor.sede_id')
            ->selectRaw('
                orden_compra_proveedor_detalles.producto_id,
                orden_proveedor.sede_id,
                SUM(GREATEST(
                    orden_compra_proveedor_detalles.cantidad_solicitada
                    - COALESCE(orden_compra_proveedor_detalles.cantidad_entregada, 0),
                    0
                )) as total_pendiente
            ')
            ->get()
            ->keyBy(fn($item) => "{$item->sede_id}:{$item->producto_id}");

        $totalOrdenes    = 0;
        $totalReferencias = 0;
        $sinCobertura    = 0;
        $cubiertosProveedor = 0;

        foreach ($ordenes as $orden) {
            $ordenTieneFaltante = false;
            $sedeStockId = $sedeIdFiltro ?: ($orden->sede_id ?: $user->sede_id);
            foreach ($orden->detalles as $detalle) {
                $stockKey  = "{$sedeStockId}:{$detalle->product_id}";
                $stock     = (float) ($stocks->get($stockKey)?->stock_total ?? 0);
                $cantidadPendienteKg = $this->calcularCantidadPendienteKg($detalle);
                $faltante = max(0, $cantidadPendienteKg - $stock);

                if ($faltante > 0) {
                    $ordenTieneFaltante = true;
                    $totalReferencias++;
                    $proveedorKey = "{$sedeStockId}:{$detalle->product_id}";
                    $solicitado = (float) ($enProveedor->get($proveedorKey)?->total_pendiente ?? 0);
                    $faltanteReal = max(0, $cantidadPendienteKg - $stock - $solicitado);

                    if ($faltanteReal > 0) {
                        $sinCobertura++;
                    } else {
                        $cubiertosProveedor++;
                    }
                }
            }
            if ($ordenTieneFaltante) $totalOrdenes++;
        }

        return response()->json([
            'total_ordenes_faltantes'    => $totalOrdenes,
            'total_referencias_faltantes' => $totalReferencias,
            'sin_cobertura'              => $sinCobertura,
            'cubiertos_proveedor'        => $cubiertosProveedor,
            'stock_total_sede'           => round((float) $stocks->sum('stock_total'), 2),
        ]);
    }

    private function calcularCantidadPendienteKg($detalle): float
    {
        $cantidadRequeridaKg = max(0, (float) ($detalle->cantidad_requerida_kg ?? 0));
        $cantidadUnidades = max(0, (float) ($detalle->cantidad ?? 0));
        $cantidadEnviada = max(0, (float) ($detalle->cantidad_enviada ?? 0));

        if ($cantidadRequeridaKg <= 0 || $cantidadUnidades <= 0) {
            return 0;
        }

        $kgPorUnidad = $cantidadRequeridaKg / $cantidadUnidades;
        $kgEnviados = min($cantidadRequeridaKg, $cantidadEnviada * $kgPorUnidad);

        return round(max(0, $cantidadRequeridaKg - $kgEnviados), 2);
    }
}
