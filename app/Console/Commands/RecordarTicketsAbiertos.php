<?php

namespace App\Console\Commands;

use App\Models\comunicaciones\Ticket;
use App\Notifications\Comunicaciones\TicketAbiertoRecordatorioNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RecordarTicketsAbiertos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recordar-tickets-abiertos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recuerda al usuario asignado que sigue teniendo tickets abiertos, aunque ya haya marcado como leída la notificación de asignación';

    private const ESTADOS_ABIERTOS = ['pendiente', 'en_proceso'];
    private const HORAS_ANTES_DE_RECORDAR = 4; // no molestar recién asignado, la notificación de asignación ya avisó
    private const COOLDOWN_HORAS = 20; // como mucho un recordatorio por ticket al día

    public function handle(): void
    {
        $tickets = Ticket::with('asignado')
            ->whereIn('estado', self::ESTADOS_ABIERTOS)
            ->whereNotNull('user_asignado_id')
            ->where('created_at', '<=', now()->subHours(self::HORAS_ANTES_DE_RECORDAR))
            ->get();

        $enviados = 0;

        foreach ($tickets as $ticket) {
            if (! $ticket->asignado) {
                continue;
            }

            $cacheKey = "recordatorio_ticket_abierto_{$ticket->id}";
            if (Cache::has($cacheKey)) {
                continue;
            }

            $ticket->asignado->notify(new TicketAbiertoRecordatorioNotification($ticket));
            Cache::put($cacheKey, true, now()->addHours(self::COOLDOWN_HORAS));
            $enviados++;
        }

        $this->info("Recordatorios de tickets abiertos enviados: {$enviados}");
    }
}
