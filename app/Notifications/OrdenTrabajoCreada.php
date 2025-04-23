<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrdenTrabajoCreada extends Notification
{
    public $ordenTrabajo;

    public function __construct($ordenTrabajo)
    {
        $this->ordenTrabajo = $ordenTrabajo;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Orden de trabajo generada')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Se ha generado una orden de trabajo con numero ' . $this->ordenTrabajo->id . ' para tu orden de compra con numero.' . $this->ordenTrabajo->ordenCompra->id)
            ->line('Cliente: ' .($this->ordenTrabajo->cliente)->nombre)
            ->line('Fecha de entrega: ' . $this->ordenTrabajo->fecha_entrega)
            ->action('Ver',  config('app.frontend_url') .'/auth/crm')
            ->line('Gracias por usar nuestro sistema.');
    }

    public function toArray($notifiable)
    {
        return [
            'mensaje' => 'Se generó una orden de trabajo para tu orden de compra',  
            'nombre' => $this->ordenTrabajo->nombre,
            'fecha_entrega' => $this->ordenTrabajo->fecha_entrega,
            'orden_trabajo_id' => $this->ordenTrabajo->id,
        ];
    }
}
