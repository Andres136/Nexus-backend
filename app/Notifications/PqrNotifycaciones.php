<?php

namespace App\Notifications;

use App\Models\Pqr;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;

class PqrNotifycaciones extends Notification
{
    use Queueable;

    private Pqr $pqr;
    private string $tipo;
    private string $emailSolicitante;

    public function __construct(Pqr $pqr, string $tipo, string $emailSolicitante)
    {
        $this->pqr = $pqr;
        $this->tipo = $tipo;
        $this->emailSolicitante = $emailSolicitante;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $codigo = $this->pqr->codigo_radicado ?? '';
        $archivoUrl = $this->pqr->archivo
            ? Storage::disk('public')->url($this->pqr->archivo)
            : null;

        // Aquí llamamos DIRECTAMENTE la vista corregida
        $vista = 'emails.pqr-asignada';  // resources/views/emails/pqr-asignada.blade.php

        return (new MailMessage)
            ->subject("Nueva PQR asignada - {$codigo}")
            ->view($vista, [
                'usuario'          => $notifiable,             // Usuario asignado
                'pqr'              => $this->pqr,              // Objeto PQR completo
                'codigo'           => $codigo,
                'emailSolicitante' => $this->emailSolicitante,
                'archivoUrl'       => $archivoUrl,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo'             => 'pqr',
            'mensaje'          => 'Se ha recibido una nueva PQR.',
            'codigo_radicado'  => $this->pqr->codigo_radicado,
            'nombre'           => $this->pqr->nombre,
            'empresa'          => $this->pqr->empresa,
            'telefono'         => $this->pqr->telefono,
            'correo'           => $this->emailSolicitante,
            'mensaje_pqr'      => $this->pqr->mensaje,
            'estado'           => 'pendiente',
        ];
    }
}
