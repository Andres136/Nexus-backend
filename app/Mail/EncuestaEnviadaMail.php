<?php

namespace App\Mail;

use App\Models\Crm\EncuestaEnvio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EncuestaEnviadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly EncuestaEnvio $envio,
        public readonly string $link,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📋 ' . $this->envio->encuesta->titulo . ' — Tu opinión nos importa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.encuesta-enviada',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
