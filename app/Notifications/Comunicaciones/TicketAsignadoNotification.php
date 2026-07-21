<?php

namespace App\Notifications\Comunicaciones;

use App\Models\comunicaciones\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketAsignadoNotification extends Notification
{
    use Queueable;

    private Ticket $ticket;

    public function __construct(Ticket $ticket)
    {
        $ticket->loadMissing(['producto', 'solicitante']);
        $this->ticket = $ticket;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'mensaje' => 'Te asignaron un ticket',
            'ticket_id' => $this->ticket->id,
            'descripcion' => $this->ticket->descripcion,
            'prioridad' => $this->ticket->prioridad,
            'solicitante' => optional($this->ticket->solicitante)->name,
            'producto' => optional($this->ticket->producto)->name,
            'url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/auth/tic/tickets?ticket=' . $this->ticket->id,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
