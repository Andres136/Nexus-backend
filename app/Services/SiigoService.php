<?php

namespace App\Services;

use App\Models\Crm\product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SiigoService
{
    private function getSiigoToken($forzarRenovacion = false)
    {
        if (!$forzarRenovacion && Cache::has('siigo_token')) {
            return Cache::get('siigo_token');
        }

        try {
            $response = Http::post('https://api.siigo.com/auth', [
                'username'   => config('services.siigo.username'),
                'access_key' => config('services.siigo.access_key'),
            ]);
        } catch (\Exception $e) {
            Log::error('Excepción al autenticar con Siigo', ['message' => $e->getMessage()]);
            return null;
        }

        if ($response->failed()) {
            Log::error('Error al autenticar con Siigo', ['response' => $response->body()]);
            return null;
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? 3600;

        if (!$token) {
            Log::error('No se recibió un token válido de Siigo', ['data' => $data]);
            return null;
        }

        Cache::put('siigo_token', $token, now()->addSeconds($expiresIn - 60));

        return $token;
    }

    public function renovarTokenManualmente()
    {
        return $this->getSiigoToken(true);
    }

    public function getProducts($params = [])
    {
        Log::info('Iniciando getProducts en SiigoService');

        $token = $this->getSiigoToken();
        if (!$token) {
            Log::error('No se pudo obtener el token de Siigo');
            return null;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Partner-Id'    => config('services.siigo.partner_id'),
            'Content-Type'  => 'application/json',
        ])->get('https://api.siigo.com/v1/products', $params);

        if ($response->status() === 401) {
            Log::warning('Token expirado, intentando renovar...');

            $token = $this->getSiigoToken(true);

            if (!$token) {
                Log::error('No se pudo renovar el token de Siigo');
                return null;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Partner-Id'    => config('services.siigo.partner_id'),
                'Content-Type'  => 'application/json',
            ])->get('https://api.siigo.com/v1/products', $params);
        }

        Log::info('Siigo API status: ' . $response->status());
        Log::info('Siigo API body: ' . $response->body());

        if ($response->failed()) {
            Log::error('Error al obtener productos de Siigo', ['error' => $response->body()]);
            return null;
        }

        return $response->json();
    }
public function getAllProducts($params = [])
{
    $allProducts = [];
    $page = 1;
    $pageSize = 50;
    $totalResults = null;

    do {
        $params['page'] = $page;
        $params['page_size'] = $pageSize; // aseguras 50 por página
        $response = $this->getProducts($params);

        if (!$response || !isset($response['results'])) {
            break;
        }

        $allProducts = array_merge($allProducts, $response['results']);

        $totalResults = $response['pagination']['total_results'] ?? count($allProducts);
        $page++;

    } while (count($allProducts) < $totalResults);

    return $allProducts;
}

    //Sincronizar productos desde Siigo
    //Sincronizar productos desde Siigo
    public function sincronizarProductosDesdeSiigo($params = [])
    {
        Log::info('Iniciando sincronización de productos desde Siigo');

        // Traer todos los productos con paginación
        $productos = $this->getAllProducts($params);

        if (!$productos || count($productos) === 0) {
            Log::error('No se pudieron obtener productos de Siigo o la respuesta es inválida');
            return [
                'productos_guardados'   => 0,
                'productos_actualizados' => 0,
                'total_procesados'      => 0,
                'productos_omitidos'    => 0,
                'detalle'               => [],
            ];
        }

        $productosGuardados   = 0;
        $productosActualizados = 0;
       
        $detalleProcesados     = [];

        foreach ($productos as $productoSiigo) {



     
            try {
                $datosProducto = [
                    'siigo_id'    => $productoSiigo['id'] ?? null,
                    'name'        => $productoSiigo['name'] ?? null,
                    'description' => $productoSiigo['description'] ?? null,
                    'code'        => $productoSiigo['code'] ?? null,
                    'categoria_id' => 1, // TODO: asignar lógica real de categorías
                    'updated_at'  => now()
                ];




                // Validar identificadores
                if (!$datosProducto['code'] && !$datosProducto['siigo_id']) {
                    Log::warning('Producto omitido por falta de identificador', $datosProducto);
                    continue;
                }

                // Buscar si ya existe
                $productoExistente = product::query()
                    ->when($datosProducto['code'], fn($q) => $q->where('code', $datosProducto['code']))
                    ->when($datosProducto['siigo_id'], fn($q) => $q->orWhere('siigo_id', $datosProducto['siigo_id']))
                    ->first();

                if ($productoExistente) {
                    $productoExistente->update($datosProducto);
                    $productosActualizados++;
                    $detalleProcesados[] = [
                        'accion' => 'actualizado',
                        'code'   => $datosProducto['code'],
                        'id'     => $datosProducto['siigo_id'],
                    ];
                    Log::info('Producto actualizado: ' . $productoExistente->name . ' (ID Siigo: ' . $productoExistente->siigo_id . ')');
                } else {
                    $datosProducto['created_at'] = now();
                    product::create($datosProducto);
                    $productosGuardados++;
                    $detalleProcesados[] = [
                        'accion' => 'creado',
                        'code'   => $datosProducto['code'],
                        'id'     => $datosProducto['siigo_id'],
                    ];
                    Log::info('Producto creado: ' . $datosProducto['name'] . ' (ID Siigo: ' . $datosProducto['siigo_id'] . ')');
                }
            } catch (\Exception $e) {
                Log::error('Error al guardar producto', [
                    'producto' => $productoSiigo['code'] ?? 'sin código',
                    'error'    => $e->getMessage()
                ]);
            }
        }

        Log::info("Sincronización completada. Productos guardados: $productosGuardados, actualizados: $productosActualizados");

        return [
            'productos_guardados'   => $productosGuardados,
            'productos_actualizados' => $productosActualizados,
   
            'total_procesados'      => $productosGuardados + $productosActualizados,
            'detalle'               => $detalleProcesados
        ];
    }
}
