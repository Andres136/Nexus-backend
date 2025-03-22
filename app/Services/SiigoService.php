<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SiigoService
{
    /**
     * Obtiene el token de Siigo y lo cachea por 24 horas.
     */
    private function getSiigoToken()
    {
        // Verificar si el token ya está en caché
        if (Cache::has('siigo_token')) {
            return Cache::get('siigo_token');
        }

        // Obtener un nuevo token de Siigo
        $response = Http::post('https://api.siigo.com/auth', [
            'username'   => env('SIIGO_USERNAME'),
            'access_key' => env('SIIGO_ACCESS_KEY'),
        ]);

        // Si la petición falló, retorna null
        if ($response->failed()) {
            Log::error('Error al autenticar con Siigo', ['response' => $response->body()]);
            return null;
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;

        if (!$token) {
            Log::error('No se recibió un token válido de Siigo', ['data' => $data]);
            return null;
        }

        // Cachear el token por 24 horas (Siigo indica que expira antes, pero así estamos cubiertos)
        Cache::put('siigo_token', $token, now()->addHours(24));

        return $token;
    }

    /**
     * Llama a Siigo para obtener la lista de productos (inventario).
     */
    public function getProducts($params = [])
    {
        Log::info('Iniciando getProducts en SiigoService');

    $token = $this->getSiigoToken();
    if (!$token) {
        Log::error('No se pudo obtener el token de Siigo');
        return null;
    }

    // Petición a Siigo
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Partner-Id'    => env('SIIGO_PARTNER_ID'),
        'Content-Type'  => 'application/json',
    ])->get('https://api.siigo.com/v1/products', $params);

    // Log de la respuesta
    Log::info('Siigo API status: ' . $response->status());
    Log::info('Siigo API body: ' . $response->body());

    // Si la petición falla (4xx o 5xx), devuelves null
    if ($response->failed()) {
        Log::error('Error al obtener productos de Siigo', ['error' => $response->body()]);
        return null;
    }

    // Si todo va bien, retorna el JSON
    return $response->json();
    }


}
