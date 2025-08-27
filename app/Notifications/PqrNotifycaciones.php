<?php

namespace App\Notifications;

use App\Models\Pqr;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

class PqrNotifycaciones extends Notification // Nombre PSR-4
{
    use Queueable;

    private Pqr $pqr;
    private string $tipo;
    private ?string $emailSolicitante;

    public function __construct(Pqr $pqr, string $tipo, ?string $emailSolicitante)
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

        // Nombre EXACTO de las vistas que SÍ existan en /resources/views/notifications/
        $vista = $this->tipo === 'admin'
            ? 'notifications.pqr-admin'      // <-- verifica que exista
            : 'notifications.pqr-usuario';    // <-- verifica que exista

        // Validación de vista y fallback opcional
        if (!View::exists($vista)) {
            Log::error("Vista de correo no encontrada: {$vista}");
            // Si tienes versiones “limpias”, intenta fallback:
            $fallback = $vista.'-limpia';
            if (View::exists($fallback)) {
                $vista = $fallback;
                Log::warning("Usando vista fallback: {$vista}");
            }
        }

        // Pasar array para Blade (coincide con tu uso $pqr['campo'])
        $pqr = $this->pqr->toArray();

        return (new MailMessage)
            ->subject(($this->tipo === 'admin' ? 'Nueva PQR - ' : 'Confirmación PQR - ') . $codigo)
            ->view($vista, [
                'usuario'          => $notifiable,
                'pqr'              => $pqr,
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
