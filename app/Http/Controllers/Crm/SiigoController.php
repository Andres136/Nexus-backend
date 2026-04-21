<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\SiigoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SiigoController extends Controller
{
  protected $siigoService;
 

  
  
  public function __construct(SiigoService $siigoService)
  {
        $this->siigoService = $siigoService;
  }
    public function index(Request $request)
    {
          //products setasplast
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 50);
        $search = $request->input('search', null);

    $response = $this->siigoService->getProducts([
            'page'      => $page,
            'page_size' => $pageSize,
            'search'    => $search
        ]);

        if (!$response) {
            return response()->json(['error' => 'Error al conectar con Siigo'], 500);
        }

        return response()->json($response);
    }

public function obtenerFacturas(Request $request)
{
    try {
        $params = [
            'created_start' => $request->get('start', '2024-01-01'),
            'created_end'   => $request->get('end', now()->format('Y-m-d')),
            'supplier_identification' => $request->get('supplier', '9001234568'), // ✔ correcto
            'page' => $request->get('page', 1),
            'page_size' => 50,
        ];

        $data = $this->siigoService->getPurchaseInvoices($params);

        if (!$data) {
            return response()->json(['error' => 'Error al obtener facturas de compra desde Siigo'], 500);
        }
        return response()->json($data);
    } catch (\Throwable $e) {
        Log::error('Error en obtenerFacturas: ' . $e->getMessage(), ['exception' => $e]);
        return response()->json([
            'error' => 'Excepción al obtener facturas de compra desde Siigo',
            'message' => $e->getMessage(),
        ], 500);
    }
}

public function stock(Request $request)
{
    return Cache::remember('stock_siigo_respuesta_final', now()->addHours(4), function () {

        set_time_limit(0);

        $page = 1;
        $pageSize = 50;
        $allProducts = collect();

        while (true) {
            $response = $this->siigoService->getProducts([
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

        return [
            'pagination' => null,
            '_links'     => null,
            'count'      => count($productos),
            'results'    => $productos,
        ];
    });
}

    

}
