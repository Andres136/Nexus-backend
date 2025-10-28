<?php

namespace App\Notifications\Crm;

use App\Models\Pqr;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class AdminNotifications extends Notification
{
    use Queueable;

    protected $pqr;
    protected $tipo;
    protected $emailDestino;


    /**
     * Create a new notification instance.
     */
    public function __construct(Pqr $pqr, string $tipo, string $emailDestino)
    {
        $this->pqr = $pqr;
        $this->tipo = $tipo;
        $this->emailDestino = $emailDestino;
    }
        //
    

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

         $archivoUrl = $this->pqr->archivo
            ? Storage::disk('public')->url($this->pqr->archivo)
            : null;

        if ($this->tipo === 'admin') {
            return (new MailMessage)
                ->subject('Nueva PQR asignada - ' . ($this->pqr->codigo_radicado ?? 'PQR Importante'))
                ->view('notifications.pqr-admin', [
                    'pqr' => $this->pqr,
                    'codigo' => $this->pqr->codigo_radicado,
                    'usuario' => $this->emailDestino,
                    'archivoUrl' => $archivoUrl,
                ]);
        } else {
            return (new MailMessage)
                ->subject('Hemos recibido tu PQR - ' . ($this->pqr->codigo_radicado ?? 'PQR Importante'))
                ->view('notifications.pqr-usuario', [
                    'pqr' => $this->pqr,
                    'admin'=> $this->emailDestino,
                    'archivoUrl' => $archivoUrl,
                ]);
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'pqr' => $this->pqr,
            'tipo' => $this->tipo,
            'codigo' => $this->pqr->codigo_radicado,
        
        ];
    }
}
