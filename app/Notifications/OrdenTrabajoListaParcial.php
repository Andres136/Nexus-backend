<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

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
        return ['database'];
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
