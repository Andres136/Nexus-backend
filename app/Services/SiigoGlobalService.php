<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SiigoGlobalService
{
    /**
     * Obtiene el token de Siigo y lo cachea por 24 horas.
     */
    private function getSiigoToken()
    {
        // Verificar si el token ya está en caché
        if (Cache::has('siigo2_token')) {
            return Cache::get('siigo2_token');
        }

        // Obtener un nuevo token de Siigo Global
        $response = Http::post('https://api.siigo.com/auth', [
            'username'   => env('SIIGO2_USERNAME'),
            'access_key' => env('SIIGO2_ACCESS_KEY'),
        ]);

        // Si la petición falló, retorna null
        if ($response->failed()) {
            Log::error('Error al autenticar con Siigo Global', ['response' => $response->body()]);
            return null;
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;

        if (!$token) {
            Log::error('No se recibió un token válido de Siigo Global', ['data' => $data]);
            return null;
        }

        // Cachear el token por 24 horas
        Cache::put('siigo2_token', $token, now()->addHours(24));

        return $token;
    }

    /**
     * Llama a Siigo Global para obtener la lista de productos (inventario).
     */
    public function getProducts($params = [])
    {
        Log::info('Iniciando getProducts en SiigoGlobalService');

        $token = $this->getSiigoToken();
        if (!$token) {
            Log::error('No se pudo obtener el token de Siigo Global');
            return null;
        }

        // Petición a Siigo Global
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Partner-Id'    => env('SIIGO2_PARTNER_ID'),
            'Content-Type'  => 'application/json',
        ])->get('https://api.siigo.com/v1/products', $params);

        // Log de la respuesta
        Log::info('Siigo Global API status: ' . $response->status());
        Log::info('Siigo Global API body: ' . $response->body());

        if ($response->failed()) {
            Log::error('Error al obtener productos de Siigo Global', ['error' => $response->body()]);
            return null;
        }

        return $response->json();
    }
}
