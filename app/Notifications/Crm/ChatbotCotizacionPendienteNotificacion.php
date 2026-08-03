<?php

namespace App\Notifications\Crm;

use App\Models\Crm\Cotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChatbotCotizacionPendienteNotificacion extends Notification
{
    use Queueable;

    public function __construct(private readonly Cotizacion $cotizacion) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Cotización #{$this->cotizacion->id} pendiente de aprobación")
            ->line('El chatbot generó una cotización y requiere tu aprobación como responsable asignado.')
            ->line('Cliente: ' . ($this->cotizacion->cliente?->nombre ?? 'Sin identificar'))
            ->line('Valor total: $' . number_format((float) $this->cotizacion->valor_total, 2, ',', '.'))
            ->action('Revisar cotización', config('app.frontend_url') . '/auth/crm/chatbot/gestion-comercial');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'chatbot_cotizacion_pendiente',
            'cotizacion_id' => $this->cotizacion->id,
            'cliente' => $this->cotizacion->cliente?->nombre,
            'url' => '/auth/crm/chatbot/gestion-comercial',
        ];
    }
}
