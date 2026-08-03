<?php

namespace App\Notifications;

use App\Models\Crm\OrdenDeTrabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrdenTrabajoCreada extends Notification
{
    use Queueable; // No encola si NO implementas ShouldQueue

    private OrdenDeTrabajo $ot;

    public function __construct(OrdenDeTrabajo $ordenTrabajo)
    {
        // Carga defensiva
        $ordenTrabajo->loadMissing(['ordenCompra.sede', 'cliente']);
        $this->ot = $ordenTrabajo;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'mensaje'          => 'Se generó una Orden de Trabajo',
            'orden_trabajo_id' => $this->ot->id,
            'fecha_entrega'    => $this->ot->fecha_entrega, // cruda (ISO/DB)
            'cliente'          => optional($this->ot->cliente)->nombre,
            'sede'             => optional(optional($this->ot->ordenCompra)->sede)->nombre,
            'url'              => rtrim(config('app.frontend_url', config('app.url')), '/') . '/auth/crm/ordenes-trabajo/' . $this->ot->id,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
