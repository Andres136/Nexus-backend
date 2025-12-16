<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryStatusMail extends Mailable
{
    public function __construct(
        public $delivery,
        public $cliente
    ) {}

    public function build()
    {
        $view = match ($this->delivery->estado) {
            'pendiente'   => 'emails.delivery.pendiente',
            'en_ruta'     => 'emails.delivery.en_ruta',
            'completado'  => 'emails.delivery.completado',
            'cancelado'   => 'emails.delivery.cancelado',
            default       => 'emails.delivery.pendiente',
        };

        return $this
            ->subject('Estado de tu entrega')
            ->view($view)
            ->with([
                'delivery' => $this->delivery,
                'cliente' => $this->cliente,
            ]);
    }
}

