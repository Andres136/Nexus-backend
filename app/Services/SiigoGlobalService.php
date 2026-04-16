<?php

namespace App\Services;

use App\Models\Crm\product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SiigoGlobalService
{
    /**
     * Obtiene el token de Siigo Global y lo cachea con la duración adecuada.
     */        
    private function getSiigoToken(bool $forzarRenovacion = false)
    {
        if (!$forzarRenovacion && Cache::has('siigo2_token')) {
            return Cache::get('siigo2_token');
        }

        try {
            $response = Http::post(config('services.siigo2.api_url') . '/auth', [
                'username'   => config('services.siigo2.username'),
                'access_key' => config('services.siigo2.access_key'),
            ]);
        } catch (\Exception $e) {
            Log::error('Excepción al autenticar con Siigo Global', ['message' => $e->getMessage()]);
            return null;
        }

        if ($response->failed()) {
            Log::error('Error al autenticar con Siigo Global', ['response' => $response->body()]);
            return null;
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? 3600;

        if (!$token) {
            Log::error('No se recibió un token válido de Siigo Global', ['data' => $data]);
            return null;
        }

        Cache::put('siigo2_token', $token, now()->addSeconds($expiresIn - 60)); // Margen de seguridad

        return $token;
    }

    /**
     * Permite forzar la renovación del token manualmente.
     */
    public function renovarTokenManualmente()
    {
        return $this->getSiigoToken(true);
    }

    /**
     * Llama a Siigo Global para obtener la lista de productos.
     */
    public function getProducts($params = [])
    {
        Log::info('Iniciando getProducts en SiigoGlobalService');

        $token = $this->getSiigoToken();
        if (!$token) {
            Log::error('No se pudo obtener el token de Siigo Global');
            return null;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Partner-Id'    => config('services.siigo2.partner_id'),
            'Content-Type'  => 'application/json',
        ])->get(config('services.siigo2.api_url') . '/v1/products', $params);

        if ($response->status() === 401) {
            Log::warning('Token expirado en Siigo Global. Renovando...');

            $token = $this->getSiigoToken(true);

            if (!$token) {
                Log::error('No se pudo renovar el token de Siigo Global');
                return null;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Partner-Id'    => config('services.siigo2.partner_id'),
                'Content-Type'  => 'application/json',
            ])->get(config('services.siigo2.api_url') . '/v1/products', $params);
        }

        Log::info('Siigo Global API status: ' . $response->status());
        Log::info('Siigo Global API body: ' . $response->body());

        if ($response->failed()) {
            Log::error('Error al obtener productos de Siigo Global', ['error' => $response->body()]);
            return null;
        }

        return $response->json();
    }

    /**
     * Obtiene todos los productos de Siigo Global manejando la paginación.
     */
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

    //Sincronizar productos desde Siigo Global
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
        $productosOmitidos    = 0;

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

     
$codigo = strtoupper(trim(preg_replace('/\s+/', '', $datosProducto['code'] ?? '')));

if ($codigo !== '' && str_starts_with($codigo, 'T')) {
    $productosOmitidos++;
    $detalleProcesados[] = [
        'accion' => 'omitido',
        'code'   => $codigo,
        'id'     => $datosProducto['siigo_id'],
        'motivo' => 'Código empieza con T'
    ];
    Log::info("Producto omitido por código T [{$codigo}] - ID: {$datosProducto['siigo_id']}");
    continue;
}

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
            'detalle'               => $detalleProcesados,
            'productos_omitidos'    => $productosOmitidos,
        ];
    }
}
