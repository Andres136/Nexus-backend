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
        $estaCompleta = $this->faltantes == 0;
        $subject = $estaCompleta ? 
            '✅ Orden de Trabajo #' . str_pad($this->ordenTrabajo->id, 6, '0', STR_PAD_LEFT) . ' - COMPLETA' :
            '⚠️ Orden de Trabajo #' . str_pad($this->ordenTrabajo->id, 6, '0', STR_PAD_LEFT) . ' - Lista Parcial';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.orden-trabajo-lista-limpia', [
                'usuario' => $notifiable,
                'ordenTrabajo' => $this->ordenTrabajo,
                'faltantes' => $this->faltantes,
                'url' => config('app.frontend_url') . '/auth/crm'
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'mensaje' => 'Tu orden tiene productos listos. Faltantes: ' . $this->faltantes,
            'nombre' => $this->ordenTrabajo->nombre,
            'fecha_entrega' => $this->ordenTrabajo->fecha_entrega,
            'orden_trabajo_id' => $this->ordenTrabajo->id,
        ];
    }
}
