<?php

namespace App\Mail;

use App\Models\Nomina\NominaLoteAprobacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NominaLoteAprobacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly NominaLoteAprobacion $lote,
        public readonly string $link,
        public readonly int $totalEmpleados,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nómina pendiente de aprobación — '.$this->lote->periodo_inicio->format('Y-m-d').' a '.$this->lote->periodo_fin->format('Y-m-d'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nomina-lote-aprobacion',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
