<?php

namespace App\Notifications;

use App\Models\Crm\Orden_Compra;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class OrdenesPorVencerNotificacion extends Notification
{
    use Queueable;

    protected $ordenCompra;

    public function __construct(Orden_Compra $ordenCompra)
    {
        $this->ordenCompra = $ordenCompra;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'orden_id' => $this->ordenCompra->id,
            "nombre" => $this->ordenCompra->nombre,
            'fecha_entrega' => $this->ordenCompra->fecha_entrega,
            'cliente' => $this->ordenCompra->cliente->nombre,
            'ubicacion_entrega' => $this->ordenCompra->ubicacion_entrega,
            'mensaje' => Carbon::now()->greaterThan($this->ordenCompra->fecha_entrega)
                ? '⚠️ ¡Orden vencida! Requiere atención inmediata.'
                : '🔔 ¡Orden por vencer! Actúa antes de la fecha límite.',
            'url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/auth/crm/detalles-compras/' . $this->ordenCompra->id,
        ];
    }
}
