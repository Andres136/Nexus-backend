<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrdenTrabajoGeneradaClienteMail extends Mailable
{
    public function __construct(
        public $ordenTrabajo,
        public $ordenCompra,
        public $cliente,
        public ?array $carteraInfo = null
    ) {}

    public function build()
    {
        return $this
            ->subject('Tu pedido avanzó a Orden de Trabajo #' . str_pad($this->ordenTrabajo->id, 6, '0', STR_PAD_LEFT))
            ->view('emails.orden-trabajo-generada-cliente')
            ->with([
                'ordenTrabajo' => $this->ordenTrabajo,
                'ordenCompra' => $this->ordenCompra,
                'cliente' => $this->cliente,
                'carteraInfo' => $this->carteraInfo,
            ]);
    }
}
