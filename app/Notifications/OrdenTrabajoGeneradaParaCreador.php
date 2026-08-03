<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class OrdenTrabajoGeneradaParaCreador extends Notification
{
    public $ordenTrabajo;
    public $carteraInfo;

    public function __construct($ordenTrabajo, ?array $carteraInfo = null)
    {
        // Cargamos relaciones necesarias si aún no vienen cargadas
        $ordenTrabajo->loadMissing(['ordenCompra.sede', 'ordenCompra.user', 'ordenCompra.cliente']);
        $this->ordenTrabajo = $ordenTrabajo;
        $this->carteraInfo = $carteraInfo;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'mensaje'          => 'Se generó orden de trabajo para tu orden de compra',
            'orden_trabajo_id' => $this->ordenTrabajo->id,
            'orden_compra_id'  => $this->ordenTrabajo->ordenCompra->id,
            'fecha_entrega'    => $this->ordenTrabajo->fecha_entrega,
            'cliente'          => optional($this->ordenTrabajo->ordenCompra->cliente)->nombre,
            'sede'             => $this->ordenTrabajo->ordenCompra->sede->nombre ?? 'Sede no asignada',
            'cartera_vencida'  => (bool) ($this->carteraInfo['tiene_vencida'] ?? false),
            'cartera_proxima'  => (bool) ($this->carteraInfo['tiene_proxima'] ?? false),
        ];
    }
}
