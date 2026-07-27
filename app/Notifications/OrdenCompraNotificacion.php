<?php

namespace App\Notifications;

use App\Models\Crm\Orden_Compra;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

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
        return ['database'];
    }

    /**
     * Notificación para la base de datos
     */
    public function toDatabase($notifiable)
    {
        return [
            'orden_compra_id' => $this->ordenCompra->id ?? 'Sin ID',
            'usuario_nombre' => $notifiable->name,
            'cliente' => optional($this->ordenCompra->cliente)->nombre ?? 'Cliente no definido',
            'fecha_entrega' => $this->ordenCompra->fecha_entrega ?? 'No especificada',
            'ubicacion_entrega' => $this->ordenCompra->ubicacion_entrega ?? 'No especificada',
            'valor_total' => $this->ordenCompra->valor_total ?? 0,
            'mensaje' => 'Esta orden de compra está vencida o próxima a vencer.',
            'url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/auth/crm/ordenes-compra/' . $this->ordenCompra->id,
        ];
    }
}
