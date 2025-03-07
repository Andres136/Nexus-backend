<?php

namespace App\Notifications;

use App\Models\Crm\Orden_Compra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class OrdenCompraNotificacion extends Notification
{
    use Queueable;

    public $ordenCompra;

    /**
     * Create a new notification instance.
     */
    public function __construct(Orden_Compra $ordenCompra)
    {
        $this->ordenCompra = $ordenCompra;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        Log::info("📧 Intentando enviar correo a: " . $notifiable->email);

        return (new MailMessage)
            ->subject('Nueva Orden de Compra Creada')
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Se ha generado una nueva orden de compra con ID: ' . $this->ordenCompra->id)
            ->line('Cliente: ' . ($this->ordenCompra->cliente)->nombre)
            ->line('Fecha de entrega: ' . $this->ordenCompra->fecha_entrega)
            ->line('Ubicación de entrega: ' . $this->ordenCompra->ubicacion_entrega)
            ->line('Valor Total: $' . number_format($this->ordenCompra->valor_total, 2))
            ->action('Ver Orden', url(config('app.url') . '/auth/crm/notifyficaciones/' . $this->ordenCompra->id))
            ->line('Gracias por usar nuestro sistema.');
    }

    /**
     * Notificación para la base de datos
     */
    public function toDatabase($notifiable)
    {
        return [
            'orden_compra_id' => $this->ordenCompra->id ?? 'Sin ID',
            'cliente' => optional($this->ordenCompra->cliente)->nombre ?? 'Cliente no definido',
            'fecha_entrega' => $this->ordenCompra->fecha_entrega ?? 'No especificada',
            'ubicacion_entrega' => $this->ordenCompra->ubicacion_entrega ?? 'No especificada',
            'valor_total' => $this->ordenCompra->valor_total ?? 0,
            'mensaje' => 'Nueva orden de compra creada.'
        ];
    }
    
}
