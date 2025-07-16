<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected string $baseUrl;
    protected string $apiUrl;
    protected string $accessToken;

    public function __construct()
    {
        $this->baseUrl     = config('services.whatsapp.api_url');
        $this->accessToken = config('services.whatsapp.access_token');

        // Construye la URL completa de envío
        $phoneId = config('services.whatsapp.phone_id');
        $this->apiUrl = "{$this->baseUrl}/{$phoneId}/messages";
    }

    public function sendTemplateMessage(string $to): array
    {
        if (! $to) {
            Log::warning('WhatsappService: número vacío');
            return ['error' => 'Número de teléfono vacío.'];
        }

        return Http::withToken($this->accessToken)
            ->post($this->apiUrl, [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'template',
                'template'          => [
                    'name'     => 'hello_world',
                    'language' => ['code' => 'en_US'],
                ],
            ])
            ->throw()
            ->json();
    }

    public function sendTextMessage(string $to, string $body): array
    {
        if (! $to || ! $body) {
            Log::warning('WhatsappService: destinatario o mensaje vacío', compact('to', 'body'));
            return ['error' => 'Datos insuficientes para enviar texto.'];
        }

        return Http::withToken($this->accessToken)
            ->post($this->apiUrl, [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $body],
            ])
            ->throw()
            ->json();
    }
}
