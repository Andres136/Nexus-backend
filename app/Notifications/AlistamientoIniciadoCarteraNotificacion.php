<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlistamientoIniciadoCarteraNotificacion extends Notification
{
    use Queueable;

    public $alistamiento;
    public $ordenCompra;
    public $carteraInfo;

    public function __construct($alistamiento, $ordenCompra, ?array $carteraInfo = null)
    {
        $this->alistamiento = $alistamiento;
        $this->ordenCompra = $ordenCompra;
        $this->carteraInfo = $carteraInfo;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('⚠️ Tu orden pasó a alistamiento — cliente con cartera pendiente')
            ->view('emails.alistamiento-iniciado-creador', [
                'usuario' => $notifiable,
                'alistamiento' => $this->alistamiento,
                'ordenCompra' => $this->ordenCompra,
                'carteraInfo' => $this->carteraInfo,
                'url' => config('app.frontend_url') . '/auth/crm/ordenes-compra/' . $this->ordenCompra->id,
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'mensaje'           => 'Tu orden pasó a alistamiento, pero tu cliente debe cartera. Evita bloqueos gestionándola cuanto antes.',
            'alistamiento_id'   => $this->alistamiento->id,
            'orden_trabajo_id'  => $this->alistamiento->orden_trabajo_id,
            'orden_compra_id'   => $this->ordenCompra->id,
            'cliente'           => optional($this->ordenCompra->cliente)->nombre,
            'cartera_vencida'   => (bool) ($this->carteraInfo['tiene_vencida'] ?? false),
            'cartera_proxima'   => (bool) ($this->carteraInfo['tiene_proxima'] ?? false),
            'total_vencido'     => (float) ($this->carteraInfo['total_vencido'] ?? 0),
            'total_proximo'     => (float) ($this->carteraInfo['total_proximo'] ?? 0),
            'facturas_vencidas' => collect($this->carteraInfo['facturas_vencidas'] ?? [])->values()->all(),
            'facturas_proximas' => collect($this->carteraInfo['facturas_proximas'] ?? [])->values()->all(),
            'url'               => rtrim(config('app.frontend_url', config('app.url')), '/') . '/auth/crm/ordenes-compra/' . $this->ordenCompra->id,
        ];
    }
}
