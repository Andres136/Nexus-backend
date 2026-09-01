<?php

namespace App\Mail;

use App\Models\Hseq\Auditoria;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuditoriaProgramadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Auditoria $auditoria,
        public readonly string $link,
    ) {}

    public function envelope(): Envelope
    {
        $departamento = $this->auditoria->departamento?->nombre ?? 'tu departamento';

        return new Envelope(
            subject: "Auditoría interna programada · {$departamento}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.auditoria-programada');
    }
}
