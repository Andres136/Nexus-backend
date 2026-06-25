<?php

namespace App\Mail;

use App\Models\CapacitacionEncuestaEnvio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CapacitacionEncuestaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly CapacitacionEncuestaEnvio $envio,
        public readonly string $link,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->envio->encuesta->titulo . ' - Evaluación de capacitación',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.capacitacion-encuesta',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
