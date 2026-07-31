<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ChatbotLeadRequest;
use App\Http\Requests\Crm\ChatbotMensajePublicoRequest;
use App\Models\Crm\ChatbotConversacion;
use App\Services\Crm\ChatbotConversacionService;
use Illuminate\Http\Request;

class ChatbotPublicoController extends Controller
{
    public function __construct(private readonly ChatbotConversacionService $conversacionService)
    {
    }

    public function configuracionPublica()
    {
        $config = $this->conversacionService->configuracionActiva();

        if (!$config->activo) {
            return response()->json(['activo' => false]);
        }

        return response()->json([
            'activo' => true,
            'nombre' => $config->nombre,
            'avatar_url' => $config->avatarUrlCompleta(),
            'mensaje_bienvenida' => $config->mensaje_bienvenida,
        ]);
    }

    public function iniciar(Request $request)
    {
        $config = $this->conversacionService->configuracionActiva();

        if (!$config->activo) {
            return response()->json(['message' => 'El chat no está disponible en este momento.'], 503);
        }

        $conversacion = $this->conversacionService->crear([
            'origen_url' => $request->input('origen_url'),
            'dominio' => $request->input('dominio'),
            'ip' => $request->ip(),
        ]);

        return response()->json(array_merge(
            ['token' => $conversacion->token],
            $this->conversacionService->estadoPublico($conversacion, null)
        ));
    }

    public function capturarLead(ChatbotLeadRequest $request, string $token)
    {
        $conversacion = ChatbotConversacion::where('token', $token)->firstOrFail();
        $this->conversacionService->capturarLead($conversacion, $request->validated());

        return response()->json(['message' => 'Datos guardados correctamente']);
    }

    public function enviarMensaje(ChatbotMensajePublicoRequest $request, string $token)
    {
        $conversacion = ChatbotConversacion::where('token', $token)->firstOrFail();

        if ($conversacion->estado === 'cerrada') {
            return response()->json(['message' => 'Esta conversación ya fue cerrada.'], 422);
        }

        $this->conversacionService->procesarMensajeLead($conversacion, $request->validated()['contenido']);

        return response()->json(
            $this->conversacionService->estadoPublico($conversacion->fresh(), null)
        );
    }

    public function solicitarAsesor(string $token)
    {
        $conversacion = ChatbotConversacion::where('token', $token)->firstOrFail();

        if ($conversacion->estado === 'cerrada') {
            return response()->json(['message' => 'Esta conversación ya fue cerrada.'], 422);
        }

        $this->conversacionService->escalarAHumano($conversacion, 'Solicitado directamente por el visitante');

        return response()->json(
            $this->conversacionService->estadoPublico($conversacion->fresh(), null)
        );
    }

    public function estado(Request $request, string $token)
    {
        $conversacion = ChatbotConversacion::where('token', $token)->firstOrFail();
        $desdeId = $request->integer('desde_id') ?: null;

        return response()->json(
            $this->conversacionService->estadoPublico($conversacion, $desdeId)
        );
    }
}
