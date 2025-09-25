<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrdenCompraProveedorMail extends Mailable 
{
    use Queueable, SerializesModels;

    public $orden;
    public $pdf;

    public function __construct($orden, $pdf)
    {
        $this->orden = $orden;
        $this->pdf = $pdf;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->orden->proveedor->correo,
            subject: "Orden de Compra #{$this->orden->numero_orden}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orden-compra-proveedor',
            with: ['orden' => $this->orden]
        );
    }
public function attachments(): array
{
    return [
        \Illuminate\Mail\Mailables\Attachment::fromData(
            fn () => $this->pdf, // Aquí llega el binario del PDF
            "orden_compra_{$this->orden->numero_orden}.pdf"
        )->withMime('application/pdf'),
    ];
}

}
