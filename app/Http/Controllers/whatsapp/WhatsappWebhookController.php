<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function __construct(private WhatsappService $whatsappService) {}

    public function handle(Request $request)
    {
        // Verificación GET
        if ($request->isMethod('get')) {
            $token    = $request->query('hub_verify_token');
            $challenge= $request->query('hub_challenge');

            Log::info('Webhook verification', compact('token', 'challenge'));

            return $token === config('services.whatsapp.verify_token')
                ? response($challenge, 200)
                : response('Token inválido', 403);
        }

        // Procesamiento POST
        $message = $request->input('entry.0.changes.0.value.messages.0', null);
        if ($message) {
            $from = $message['from'];
            $body = $message['text']['body'] ?? '';

            Log::info('Mensaje entrante', compact('from', 'body'));

            $reply = $this->procesarTexto($body);
            $this->whatsappService->sendTextMessage($from, $reply);
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function procesarTexto(string $texto): string
    {
        return match (strtolower(trim($texto))) {
            'hola'       => '¡Hola! 👋 Bienvenido a Setasplast.',
            'productos'  => 'Nuestro catálogo: https://setasplast.com/catalogo',
            'cotización' => 'Envíame producto y cantidad para cotizar.',
            default      => 'No entendí tu mensaje. Prueba "hola", "productos" o "cotización".',
        };
    }
}

