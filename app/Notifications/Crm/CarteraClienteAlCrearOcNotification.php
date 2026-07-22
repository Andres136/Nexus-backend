<?php

namespace App\Notifications\Crm;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CarteraClienteAlCrearOcNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected $ordenCompra,
        protected array $resumen
    ) {
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $ocNum = str_pad((int) $this->ordenCompra->id, 6, '0', STR_PAD_LEFT);

        return (new MailMessage)
            ->subject('⚠️ Cartera pendiente del cliente de tu Orden de Compra #' . $ocNum)
            ->view('notifications.cartera-cliente-nueva-oc', [
                'usuario' => $notifiable,
                'ordenCompra' => $this->ordenCompra,
                'resumen' => $this->resumen,
                'url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/auth/crm/ordenes-compra/' . $this->ordenCompra->id,
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'mensaje'           => 'El cliente de tu nueva Orden de Compra tiene cartera pendiente.',
            'orden_compra_id'   => $this->ordenCompra->id,
            'cliente'           => optional($this->ordenCompra->cliente)->nombre,
            'cartera_vencida'   => (bool) ($this->resumen['tiene_vencida'] ?? false),
            'cartera_proxima'   => (bool) ($this->resumen['tiene_proxima'] ?? false),
            'total_vencido'     => (float) ($this->resumen['total_vencido'] ?? 0),
            'total_proximo'     => (float) ($this->resumen['total_proximo'] ?? 0),
            'facturas_vencidas' => collect($this->resumen['facturas_vencidas'] ?? [])->values()->all(),
            'facturas_proximas' => collect($this->resumen['facturas_proximas'] ?? [])->values()->all(),
            'url'               => rtrim(config('app.frontend_url', config('app.url')), '/') . '/auth/crm/ordenes-compra/' . $this->ordenCompra->id,
        ];
    }
}
