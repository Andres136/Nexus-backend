<?php

namespace App\Services;

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
}
