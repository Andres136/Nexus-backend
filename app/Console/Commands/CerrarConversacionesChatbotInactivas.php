<?php

namespace App\Console\Commands;

use App\Models\Crm\ChatbotConversacion;
use Illuminate\Console\Command;

class CerrarConversacionesChatbotInactivas extends Command
{
    protected $signature = 'app:cerrar-conversaciones-chatbot-inactivas';

    protected $description = 'Cierra automáticamente conversaciones del chatbot sin actividad reciente (24h sin mensajes)';

    private const HORAS_INACTIVIDAD = 24;

    public function handle(): void
    {
        $cerradas = ChatbotConversacion::whereIn('estado', ['bot', 'esperando_humano', 'asignada'])
            ->where('ultima_actividad_at', '<=', now()->subHours(self::HORAS_INACTIVIDAD))
            ->update(['estado' => 'cerrada', 'cerrada_at' => now()]);

        $this->info("Conversaciones del chatbot cerradas por inactividad: {$cerradas}");
    }
}
