<?php

namespace App\Mail;

use App\Models\CapacitacionActaEnvio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CapacitacionActaFirmaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly CapacitacionActaEnvio $envio, public readonly string $link) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Acta {$this->envio->acta->numero} pendiente de firma");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.capacitacion-acta-firma');
    }
}
