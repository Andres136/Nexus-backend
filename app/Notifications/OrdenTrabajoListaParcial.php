<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrdenTrabajoListaParcial extends Notification
{
    public $ordenTrabajo;
    public $faltantes;

    public function __construct($ordenTrabajo, $faltantes)
    {
        $this->ordenTrabajo = $ordenTrabajo;
        $this->faltantes = $faltantes;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $mensaje = new MailMessage;
        $mensaje->subject('Orden de trabajo lista (parcial o completa)')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Tu orden de trabajo numero ' .$this->ordenTrabajo->id .' ya tiene productos listos para entrega.');

        if ($this->faltantes > 0) {
            $mensaje->line('⚠️ Aún hay ' . $this->faltantes . ' unidades pendientes.');
        } else {
            $mensaje->line('✅ Tu orden está completamente lista.');
        }

        $mensaje->action('Ver ',config('app.frontend_url') . '/auth/crm')
            ->line('Gracias por usar nuestro sistema.');

        return $mensaje;
    }

    public function toArray($notifiable)
    {
        return [
            'mensaje' => 'Tu orden tiene productos listos. Faltantes: ' . $this->faltantes,
            'orden_trabajo_id' => $this->ordenTrabajo->id,
        ];
    }
}
