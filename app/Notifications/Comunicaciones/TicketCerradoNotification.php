<?php

namespace App\Notifications\Comunicaciones;

use App\Models\comunicaciones\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class TicketCerradoNotification extends Notification
{
    use Queueable;

    private Ticket $ticket;
    private ?string $comentarioCierre;

    public function __construct(Ticket $ticket, ?string $comentarioCierre = null)
    {
        $ticket->loadMissing(['producto', 'asignado', 'departamento']);
        $this->ticket = $ticket;
        $this->comentarioCierre = $comentarioCierre;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'mensaje' => 'Tu ticket fue cerrado',
            'ticket_id' => $this->ticket->id,
            'descripcion' => $this->ticket->descripcion,
            'prioridad' => $this->ticket->prioridad,
            'asignado' => optional($this->ticket->asignado)->name,
            'producto' => optional($this->ticket->producto)->name,
            'url' => $this->ticketUrl(),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket finalizado - #{$this->ticket->id}")
            ->view('emails.tickets.cerrado', [
                'usuario' => $notifiable,
                'ticket' => $this->ticket,
                'url' => $this->ticketUrl(),
                'adjuntos' => $this->adjuntos(),
                'comentarioCierre' => $this->comentarioCierre,
            ]);
    }

    private function ticketUrl(): string
    {
        return rtrim(config('app.frontend_url', config('app.url')), '/') . '/auth/tic/tickets?ticket=' . $this->ticket->id;
    }

    private function adjuntos(): array
    {
        return collect([$this->ticket->archivo])
            ->merge($this->ticket->archivos ?? [])
            ->filter()
            ->map(fn (string $path) => [
                'nombre' => basename($path),
                'url' => Storage::disk('public')->url($path),
            ])
            ->values()
            ->all();
    }
}
