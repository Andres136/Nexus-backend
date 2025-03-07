<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected $apiUrl;
    protected $businessId;
    protected $accessToken;
    protected $phoneId;

    public function __construct()
    {
        $this->apiUrl = "https://graph.facebook.com/v22.0/{$this->phoneId}/messages";
        $this->businessId = env('WHATSAPP_BUSINESS_ID');
        $this->accessToken = env('WHATSAPP_ACCESS_TOKEN');
        $this->phoneId = env('WHATSAPP_PHONE_ID');
    }

    public function sendMessage($telefono)
    {
        if (empty($telefono)) {
            return ['error' => 'Número de teléfono vacío.'];
        }
    
        $url = "https://graph.facebook.com/v22.0/{$this->phoneId}/messages";
    
        $response = Http::withToken($this->accessToken)->post($url, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $telefono,
            'type' => 'template',
            'template' => [
                'name' => 'hello_world',
                'language' => ['code' => 'en_US']
            ]
        ]);
    
        return $response->json();
    }
    
}
