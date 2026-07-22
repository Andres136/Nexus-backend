<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AlistamientoIniciadoClienteMail extends Mailable
{
    public function __construct(
        public $alistamiento,
        public $ordenCompra,
        public $cliente,
        public ?array $carteraInfo = null
    ) {}

    public function build()
    {
        return $this
            ->subject('Tu pedido está en alistamiento — Orden #' . str_pad($this->ordenCompra->id, 6, '0', STR_PAD_LEFT))
            ->view('emails.alistamiento-iniciado-cliente')
            ->with([
                'alistamiento' => $this->alistamiento,
                'ordenCompra' => $this->ordenCompra,
                'cliente' => $this->cliente,
                'carteraInfo' => $this->carteraInfo,
            ]);
    }
}
