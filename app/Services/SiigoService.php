<?php

namespace App\Services;

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

    
}


