<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrdenTrabajoCreada extends Notification
{
    public $ordenTrabajo;

    public function __construct($ordenTrabajo)
    {
        // Cargamos relaciones necesarias si aún no vienen cargadas
        $ordenTrabajo->loadMissing(['ordenCompra.sede', 'cliente']);
        $this->ordenTrabajo = $ordenTrabajo;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $ordenCompra = $this->ordenTrabajo->ordenCompra;
        $sedeNombre = $ordenCompra->sede->nombre ?? 'Sede no asignada';

        return (new MailMessage)
            ->subject('Orden de trabajo generada')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Se ha generado una orden de trabajo con número ' . $this->ordenTrabajo->id . ' para la orden de compra N° ' . $ordenCompra->id)
            ->line('Sede: ' . $sedeNombre)
            ->line('Cliente: ' . $this->ordenTrabajo->cliente->nombre)
            ->line('Fecha de entrega: ' . $this->ordenTrabajo->fecha_entrega)
            ->action('Ver orden de trabajo', config('app.frontend_url') . '/auth/crm')
            ->line('Gracias por usar nuestro sistema.');
    }

    public function toArray($notifiable)
    {
        return [
            'mensaje'          => 'Se generó una orden de trabajo',
            'orden_trabajo_id' => $this->ordenTrabajo->id,
            'fecha_entrega'    => $this->ordenTrabajo->fecha_entrega,
            'sede'             => $this->ordenTrabajo->ordenCompra->sede->nombre ?? 'Sede no asignada',
        ];
    }
}

