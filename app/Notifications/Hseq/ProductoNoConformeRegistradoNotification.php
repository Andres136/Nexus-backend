<?php

namespace App\Notifications\Hseq;

use App\Models\Hseq\ProductoNoConforme;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductoNoConformeRegistradoNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ProductoNoConforme $productoNoConforme)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $origenLabel = match ($this->productoNoConforme->origen) {
            'cliente' => optional($this->productoNoConforme->cliente)->nombre,
            'proveedor' => optional($this->productoNoConforme->proveedor)->nombre,
            default => 'Proceso interno',
        };

        return [
            'mensaje' => 'Se registró un nuevo producto no conforme'
                . ($origenLabel ? " ({$origenLabel})" : ''),
            'producto_no_conforme_id' => $this->productoNoConforme->id,
            'origen' => $this->productoNoConforme->origen,
            'tipo_falla' => $this->productoNoConforme->tipo_falla,
            'url' => rtrim(config('app.frontend_url', config('app.url')), '/')
                . '/auth/crm/no-conformidades/' . $this->productoNoConforme->id . '/gestionar',
        ];
    }
}
