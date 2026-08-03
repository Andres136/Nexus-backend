<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ChatbotConfiguracionRequest;
use App\Models\Crm\ChatbotConfiguracion;
use App\Services\Crm\ChatbotConversacionService;
use App\Services\Crm\ChatbotSitioWebService;
use Illuminate\Support\Facades\Storage;

class ChatbotConfiguracionController extends Controller
{
    public function __construct(
        private readonly ChatbotConversacionService $conversacionService,
        private readonly ChatbotSitioWebService $sitioWebService
    ) {}

    public function show()
    {
        return response()->json($this->conConAvatarPublico($this->conversacionService->configuracionActiva()));
    }

    public function update(ChatbotConfiguracionRequest $request)
    {
        $config = $this->conversacionService->configuracionActiva();
        $datos = $request->validated();
        unset($datos['avatar']);

        $nuevaUrl = $datos['sitio_web_url'] ?? null;
        $nuevaUrlProductos = $datos['sitio_web_productos_url'] ?? null;
        if ($nuevaUrl !== $config->sitio_web_url || $nuevaUrlProductos !== $config->sitio_web_productos_url) {
            $contextos = [];
            if ($nuevaUrl) $contextos[] = "SITIO PRINCIPAL:\n" . $this->sitioWebService->extraer($nuevaUrl);
            if ($nuevaUrlProductos) $contextos[] = "PRODUCTOS Y SOLUCIONES:\n" . $this->sitioWebService->extraer($nuevaUrlProductos);
            $datos['sitio_web_contexto'] = $contextos ? implode("\n\n", $contextos) : null;
            $datos['sitio_web_actualizado_at'] = $contextos ? now() : null;
        }

        if ($request->hasFile('avatar')) {
            if ($config->avatar_url && !str_starts_with($config->avatar_url, 'http')) {
                Storage::disk('public')->delete($config->avatar_url);
            }
            $datos['avatar_url'] = $request->file('avatar')->store('chatbot', 'public');
        }

        $config->update($datos);

        return response()->json([
            'message' => 'Configuración actualizada correctamente',
            'configuracion' => $this->conConAvatarPublico($config->fresh()),
        ]);
    }

    private function conConAvatarPublico(ChatbotConfiguracion $config): array
    {
        return array_merge($config->toArray(), [
            'avatar_url' => $config->avatarUrlCompleta(),
            'sitio_web_contexto_caracteres' => mb_strlen($config->sitio_web_contexto ?? ''),
        ]);
    }
}
