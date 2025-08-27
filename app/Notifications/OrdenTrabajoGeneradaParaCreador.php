<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrdenTrabajoGeneradaParaCreador extends Notification
{
    public $ordenTrabajo;

    public function __construct($ordenTrabajo)
    {
        // Cargamos relaciones necesarias si aún no vienen cargadas
        $ordenTrabajo->loadMissing(['ordenCompra.sede', 'ordenCompra.user']);
        $this->ordenTrabajo = $ordenTrabajo;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('✅ Orden de Trabajo Generada - OC #' . str_pad($this->ordenTrabajo->ordenCompra->id, 6, '0', STR_PAD_LEFT))
            ->view('emails.orden-trabajo-generada-creador', [
                'usuario' => $notifiable,
                'ordenTrabajo' => $this->ordenTrabajo,
                'ordenCompra' => $this->ordenTrabajo->ordenCompra,
                'url' => config('app.frontend_url') . '/auth/crm/ordenes-compra/' . $this->ordenTrabajo->ordenCompra->id
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'mensaje'          => 'Se generó orden de trabajo para tu orden de compra',
            'orden_trabajo_id' => $this->ordenTrabajo->id,
            'orden_compra_id'  => $this->ordenTrabajo->ordenCompra->id,
            'fecha_entrega'    => $this->ordenTrabajo->fecha_entrega,
            'sede'             => $this->ordenTrabajo->ordenCompra->sede->nombre ?? 'Sede no asignada',
        ];
    }
}
