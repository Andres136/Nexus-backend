<?php

namespace App\Notifications\Crm;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FacturaCarteraNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $factura;
    protected $tipo;
    public function __construct($factura, $tipo)
    {
        $this->factura = $factura;
        $this->tipo = $tipo;

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
    return (new MailMessage)
        ->subject('Factura en cartera')
        ->view('notifications.factura-cartera-' . $this->tipo, [
            'factura' => $this->factura,
            'url' => config('app.frontend_url') . '/auth/crm/gestion-cartera/' . $this->factura->id
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
