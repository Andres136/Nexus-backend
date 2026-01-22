<?php

namespace App\Notifications\Traslados;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificacionTrasladoCreado extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $traslado)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $aprobarUrl = route('email.traslados.inventario.aprobar', ['traslado' => $this->traslado->id, 'user' => $notifiable->id]);
        $rechazarUrl = route('email.traslados.inventario.rechazar', ['traslado' => $this->traslado->id, 'user' => $notifiable->id]);

        return (new MailMessage)
             ->subject('Traslado pendiente de aprobacion -Inventario')
             ->view('emails.traslados.pendiente_inventario', [
                 'traslado' => $this->traslado,
                 'aprobarUrl' => $aprobarUrl,
                 'rechazarUrl' => $rechazarUrl
             ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
