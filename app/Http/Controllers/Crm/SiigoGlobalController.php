<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\SiigoGlobalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SiigoGlobalController extends Controller
{
    protected $siigoGlobalService;

    public function __construct(SiigoGlobalService $siigoGlobalService)
    {
        $this->siigoGlobalService = $siigoGlobalService;
    }
  
    public function index(Request $request)
    {
        set_time_limit(0);

        $page = 1;
        $pageSize = 50;  // Ajusta según tus necesidades
        $allProducts = collect();
    
        while (true) {
            // Llamada a la API
            $response = $this->siigoGlobalService->getProducts([
                'page'      => $page,
                'page_size' => $pageSize
            ]);
    
            // Verificamos que la respuesta tenga resultados
            if (!$response || !isset($response['results'])) {
                // Rompe si hay error o no hay 'results'
                break;
            }
    
            // Acumula los productos de la página actual
            $allProducts = $allProducts->merge($response['results']);
    
            // Verifica si existe el link "next"
            $links = $response['_links'] ?? [];
            if (!isset($links['next'])) {
                // Si no hay "next", significa que no hay más páginas
                break;
            }
    
            $page++;
        }
    
        // Ahora $allProducts contiene todo lo que haya devuelto la API
        // Aquí puedes mapear y calcular saldos, etc.
        // ...
    
        return response()->json([
            'count'   => $allProducts->count(),
            'results' => $allProducts,
        ]);
    }

  

    // public function stock(Request $request)
    // {
      
    //     set_time_limit(0);

    //     $page = 1;
    //     $pageSize = 50;  // Ajusta según tus necesidades
    //     $allProducts = collect();
    
    //     while (true) {
    //         // Llamada a la API
    //         $response = $this->siigoGlobalService->getProducts([
    //             'page'      => $page,
    //             'page_size' => $pageSize
    //         ]);
    
    //         // Verificamos que la respuesta tenga resultados
    //         if (!$response || !isset($response['results'])) {
    //             // Rompe si hay error o no hay 'results'
    //             break;
    //         }
    
    //         // Acumula los productos de la página actual
    //         $allProducts = $allProducts->merge($response['results']);
    
    //         // Verifica si existe el link "next"
    //         $links = $response['_links'] ?? [];
    //         if (!isset($links['next']) || count($response['results']) < $pageSize) {
    //             // Si no hay "next", significa que no hay más páginas
    //             break;
    //         }
    
    //         $page++;
    //     }
    
    //     // Ahora $allProducts contiene todo lo que haya devuelto la API
    //     // Aquí puedes mapear y calcular saldos, etc.
    //     // ...
    
  
    

    
    
    //     // 1. Obtener órdenes de trabajo pendientes
    //     $ordenesTrabajoBase = DB::table('orden__compra__detalles')
    //         ->join('orden__compras', 'orden__compra__detalles.orden_compra_id', '=', 'orden__compras.id')
    //         ->where('orden__compras.estado_id', 1)
    //         ->select(
    //             DB::raw("UPPER(TRIM(CONCAT(
    //                 CASE WHEN orden__compra__detalles.largo_cm = 100 
    //                      THEN '1' 
    //                      ELSE CAST(orden__compra__detalles.largo_cm AS DECIMAL(10,0)) 
    //                 END,
    //                 '*',
    //                 CASE WHEN orden__compra__detalles.ancho_cm = 100 
    //                      THEN '1'
    //                      ELSE CAST(orden__compra__detalles.ancho_cm AS DECIMAL(10,0))
    //                 END,
    //                 ' ',
    //                 orden__compra__detalles.descripcion, ' CAL ',
    //                 CAST(orden__compra__detalles.calibre AS DECIMAL(10,1))
    //             ))) AS descripcion"),
    //             DB::raw('SUM(orden__compra__detalles.cantidad_requerida_kg) as total_requerida')
    //         )
    //         ->groupBy(
    //             'orden__compra__detalles.largo_cm',
    //             'orden__compra__detalles.ancho_cm',
    //             'orden__compra__detalles.descripcion',
    //             'orden__compra__detalles.calibre'
    //         )
    //         ->get();
    
    //     // 2. Normalizar las órdenes de trabajo
    //     $ordenesTrabajo = $ordenesTrabajoBase->mapWithKeys(function ($item) {
    //         $descOriginal = $item->descripcion; 
    //         $descSinPaq = preg_replace('/PAQ\s*\*\s*\d+/i', '', $descOriginal);
    //         $descFinal = trim(preg_replace('/\s+/', ' ', $descSinPaq));
    //         return [
    //             $descFinal => (object)[
    //                 'descripcion'     => $descFinal,
    //                 'total_requerida' => $item->total_requerida,
    //             ]
    //         ];
    //     });
    
    //     // 3. Función para normalizar la descripción de Siigo
    //     function normalizarDescripcionSiigo($descSiigo)
    //     {
    //         if (!is_string($descSiigo)) {
    //             return 'SIN DESCRIPCION';
    //         }
    //         $descSiigo = strtoupper($descSiigo);
    //         $descSiigo = preg_replace('/PAQ\s*\*\s*\d+/i', '', $descSiigo);
    //         $descSiigo = trim(preg_replace('/\s+/', ' ', $descSiigo));
    //         return $descSiigo;
    //     }
    
    //     // 4. Procesar la página de productos para calcular el saldo
    //     $productos = collect($allProducts)->map(function ($prod) use ($ordenesTrabajo) {
    //         $descSiigo = normalizarDescripcionSiigo($prod['description'] ?? $prod['name'] ?? '');
    //         $cantidadDisponible = isset($prod['available_quantity']) ? (float)$prod['available_quantity'] : 0;
    //         $cantidadRequerida  = isset($ordenesTrabajo[$descSiigo]) ? (float)$ordenesTrabajo[$descSiigo]->total_requerida : 0;
    //         $saldo = $cantidadDisponible - $cantidadRequerida;
    
    //         return [
    //             'id'                => $prod['id'] ?? null,
    //             'codigo'            => $prod['code'] ?? null,
    //             'name'              => $prod['name'] ?? null,
    //             'bodega'            => $prod['warehouses'] ?? null,
    //             'descripcion'       => $descSiigo,
    //             'available_quantity'=> $cantidadDisponible,
    //             'cantidad_requerida'=> $cantidadRequerida,
    //             'saldo'             => $saldo,
    //             'alerta'            => $saldo < 0 ? '⚠️ Falta stock' : '✅ Stock suficiente'
    //         ];
    //     });
    
    //     // Opcional: Agregar la información de paginación de Siigo
    //     $pagination = $response['pagination'] ?? null;
    //     $links      = $response['_links'] ?? null;
    
    //     // Retornar un objeto con los productos y la paginación
    //     return response()->json([
    //         'pagination' => $pagination,
    //         '_links'     => $links,
    //         'count'      => $productos->count(),
    //         'results'    => $productos,
    //     ], 200);
    // }
    
    
    
    public function stock(Request $request)
    {
        return Cache::remember('stock_siigo_global_respuesta_final', now()->addHours(4), function () {
            set_time_limit(0);
    
            $page = 1;
            $pageSize = 50;
            $allProducts = collect();
    
            while (true) {
                $response = $this->siigoGlobalService->getProducts([
                    'page'      => $page,
                    'page_size' => $pageSize
                ]);
    
                if (!$response || !isset($response['results'])) {
                    break;
                }
    
                $allProducts = $allProducts->merge($response['results']);
    
                $links = $response['_links'] ?? [];
                if (!isset($links['next']) || count($response['results']) < $pageSize) {
                    break;
                }
    
                $page++;
            }
    
            // 1. Obtener órdenes de trabajo pendientes
            $ordenesTrabajoBase = DB::table('orden__compra__detalles')
                ->join('orden__compras', 'orden__compra__detalles.orden_compra_id', '=', 'orden__compras.id')
                ->where('orden__compras.estado_id', 1)
                ->select(
                    DB::raw("UPPER(TRIM(CONCAT(
                        CASE WHEN orden__compra__detalles.largo_cm = 100 
                            THEN '1' 
                            ELSE CAST(orden__compra__detalles.largo_cm AS DECIMAL(10,0)) 
                        END,
                        '*',
                        CASE WHEN orden__compra__detalles.ancho_cm = 100 
                            THEN '1'
                            ELSE CAST(orden__compra__detalles.ancho_cm AS DECIMAL(10,0))
                        END,
                        ' ',
                        orden__compra__detalles.descripcion, ' CAL ',
                        CAST(orden__compra__detalles.calibre AS DECIMAL(10,1))
                    ))) AS descripcion"),
                    DB::raw('SUM(orden__compra__detalles.cantidad_requerida_kg) as total_requerida')
                )
                ->groupBy(
                    'orden__compra__detalles.largo_cm',
                    'orden__compra__detalles.ancho_cm',
                    'orden__compra__detalles.descripcion',
                    'orden__compra__detalles.calibre'
                )
                ->get();
    
            // 2. Normalizar las órdenes de trabajo
            $ordenesTrabajo = $ordenesTrabajoBase->mapWithKeys(function ($item) {
                $descOriginal = $item->descripcion; 
                $descSinPaq = preg_replace('/PAQ\s*\*\s*\d+/i', '', $descOriginal);
                $descFinal = trim(preg_replace('/\s+/', ' ', $descSinPaq));
                return [
                    $descFinal => (object)[
                        'descripcion'     => $descFinal,
                        'total_requerida' => $item->total_requerida,
                    ]
                ];
            });
    
            // 3. Función para normalizar la descripción de Siigo
            function normalizarDescripcionSiigo($descSiigo)
            {
                if (!is_string($descSiigo)) {
                    return 'SIN DESCRIPCION';
                }
                $descSiigo = strtoupper($descSiigo);
                $descSiigo = preg_replace('/PAQ\s*\*\s*\d+/i', '', $descSiigo);
                $descSiigo = trim(preg_replace('/\s+/', ' ', $descSiigo));
                return $descSiigo;
            }
    
            // 4. Procesar la página de productos para calcular el saldo
            $productos = collect($allProducts)->map(function ($prod) use ($ordenesTrabajo) {
                $descSiigo = normalizarDescripcionSiigo($prod['description'] ?? $prod['name'] ?? '');
                $cantidadDisponible = isset($prod['available_quantity']) ? (float)$prod['available_quantity'] : 0;
                $cantidadRequerida  = isset($ordenesTrabajo[$descSiigo]) ? (float)$ordenesTrabajo[$descSiigo]->total_requerida : 0;
                $saldo = $cantidadDisponible - $cantidadRequerida;
    
                return [
                    'id'                => $prod['id'] ?? null,
                    'codigo'            => $prod['code'] ?? null,
                    'name'              => $prod['name'] ?? null,
                    'bodega'            => $prod['warehouses'] ?? null,
                    'descripcion'       => $descSiigo,
                    'available_quantity'=> $cantidadDisponible,
                    'cantidad_requerida'=> $cantidadRequerida,
                    'saldo'             => $saldo,
                    'alerta'            => $saldo < 0 ? '⚠️ Falta stock' : '✅ Stock suficiente'
                ];
            });
    
            // Opcional: Agregar la información de paginación de Siigo
            $pagination = $response['pagination'] ?? null;
            $links      = $response['_links'] ?? null;
    
            return [
                'pagination' => $pagination,
                '_links'     => $links,
                'count'      => $productos->count(),
                'results'    => $productos,
            ];
        });
    }
    

    }
 

