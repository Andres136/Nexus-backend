<?php

namespace App\Notifications\Crm;

use App\Models\Pqr;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PqrAsignadanotificacion extends Notification
{
    use Queueable;
    public $pqr;

    /**
     * Create a new notification instance.
     */
    public function __construct(Pqr $pqr)
    {
        $this->pqr = $pqr;
    }
   

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
        $codigo = $this->pqr->codigo_radicado ?? '---';
        $archivoUrl = $this->pqr->archivo ? url('/storage/' . $this->pqr->archivo) : null;
    
        $mail = (new MailMessage)
            ->subject('PQR Asignada')
            ->greeting('📥 PQR Asignada')
            ->line('🔹 Código de radicado: **' . $codigo . '**')
            ->line('📩 Mensaje: ' . $this->pqr->mensaje)
            ->line('👤 Nombre: ' . $this->pqr->nombre)
            ->line('🏢 Empresa: ' . $this->pqr->empresa)
            ->line('📞 Teléfono: ' . $this->pqr->telefono)
            ->line('🕒 Estado: Pendiente');
    
        if ($archivoUrl) {
            $mail->action('📎 Ver archivo adjunto', $archivoUrl);
        }
    
        return $mail->salutation('Sistema de PQR - SETASPLAST');
    }
    

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'pqr_id' => $this->pqr->id,
            'codigo_radicado' => $this->pqr->codigo_radicado,
            'empresa' => $this->pqr->empresa,
            'mensaje' => $this->pqr->mensaje,
            'archivo' => $this->pqr->archivo,
            'asignado_a' => $this->pqr->asignado_a,
            'created_at' => now(),
        ];
    }
}
