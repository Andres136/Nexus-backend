<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class OrdenCompraCarteraClienteMail extends Mailable
{
    public function __construct(
        public $ordenCompra,
        public $cliente,
        public array $resumen
    ) {
    }

    public function build()
    {
        $ocNum = str_pad((int) $this->ordenCompra->id, 6, '0', STR_PAD_LEFT);

        return $this
            ->subject('Tu Orden de Compra #' . $ocNum . ' — cartera pendiente')
            ->view('emails.orden-compra-cartera-cliente')
            ->with([
                'ordenCompra' => $this->ordenCompra,
                'cliente' => $this->cliente,
                'resumen' => $this->resumen,
            ]);
    }
}
