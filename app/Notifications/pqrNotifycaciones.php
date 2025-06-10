<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class pqrNotifycaciones extends Notification
{
    use Queueable;
  
    private $pqr;
    private $tipo;
    private $emailSolicitante;

    /**
     * Create a new notification instance.
     */
    public function __construct($pqr, $tipo, $emailSolicitante )
    {

        $this->pqr = $pqr;
        $this->tipo = $tipo;
        $this->emailSolicitante = $emailSolicitante;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $codigo = $this->pqr['codigo_radicado'] ?? '---';
        $archivoUrl = $this->pqr['archivo'] ? url('/storage/' . $this->pqr['archivo']) : null;
    
        if ($this->tipo === 'admin') {
            $mail = (new MailMessage)
                ->subject('Nueva PQR Recibida')
                ->greeting('📥 Nueva solicitud registrada')
                ->line('🔹 Código de radicado: **' . $codigo . '**')
                ->line('📩 Mensaje: ' . $this->pqr['mensaje'])
                ->line('👤 Nombre: ' . $this->pqr['nombre'])
                ->line('🏢 Empresa: ' . $this->pqr['empresa'])
                ->line('📞 Teléfono: ' . $this->pqr['telefono'])
                ->line('📧 Correo del solicitante: ' . $this->emailSolicitante)
                ->line('🕒 Estado: Pendiente');
    
            if ($archivoUrl) {
                $mail->action('📎 Ver archivo adjunto', $archivoUrl);
            }
    
            return $mail->salutation('Sistema de PQR - SETASPLAST');
        }
    
        // Tipo: usuario
        $mail = (new MailMessage)
            ->subject('Confirmación de recepción de PQR')
            ->greeting('Estimado/a Cliente,')
            ->line('Hemos recibido su solicitud correctamente.')
            ->line('🆔 Código de radicado: **' . $codigo . '**')
            ->line('📩 Mensaje: ' . $this->pqr['mensaje'])
            ->line('')
            ->line('Nuestro equipo la revisará y responderá en máximo 15 días hábiles.')
            ->line('Para atención prioritaria, puede comunicarse al 3112890067.');
    
        if ($archivoUrl) {
            $mail->action('📎 Ver archivo que adjuntó', $archivoUrl);
        }
    
        return $mail->salutation('Atentamente, equipo de soporte SETASPLAST SAS BIC');
    }
    


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
       //guardarla y enviarla auna url

       return [
           'tipo' =>'pqr',
              'mensaje' => 'Se ha recibido una nueva PQR.',
              'codigo_radicado' => $this->pqr['codigo_radicado'],
                'nombre' => $this->pqr['nombre'],
                'empresa' => $this->pqr['empresa'],
                'telefono' => $this->pqr['telefono'],
                'correo' => $this->emailSolicitante,
                'mensaje_pqr' => $this->pqr['mensaje'],
                'estado' => 'pendiente',
     
       ];

    }
}

