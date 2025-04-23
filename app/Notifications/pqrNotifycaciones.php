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
        if ($this->tipo === 'admin') {
            return (new MailMessage)
                ->subject('Nueva PQR Recibida')
                ->line('Se ha recibido una nueva PQR.')
            
                ->line('Mensaje: ' . $this->pqr['mensaje'])
                ->line('Nombre : ' . $this->pqr['nombre'])
                ->line('Empresa: ' . $this->pqr['empresa'])
                ->line('Teléfono : ' . $this->pqr['telefono'])
                ->line('Correo del solicitante: ' . $this->emailSolicitante)
                ->line('Por favor, revisa la solicitud.');
        } else {
            return (new MailMessage)
            ->subject('Confirmación de recepción de PQR')
            ->line('Estimado/a Cliente,')
            ->line('Le agradecemos por contactarnos. Confirmamos la recepción de su solicitud, la cual contiene los siguientes detalles:')
            ->line('')
            ->line('📩 *Mensaje:* ' . $this->pqr['mensaje'])
            ->line('')
            ->line('Nuestro equipo de soporte ha iniciado la revisión de la información proporcionada. En un plazo de hasta 15 días hábiles nos comunicaremos con usted para informarle los próximos pasos o, si es necesario, solicitar información adicional.')
            ->line('')
            ->line('Si su solicitud requiere atención prioritaria, puede comunicarse con nuestra línea de atención al cliente al [Número de Teléfono].')
            ->line('')
            ->line('Agradecemos su paciencia y la confianza depositada en nosotros.')
            ->line('')
            ->line('Atentamente,')
            ->line('El equipo de soporte de SETASPLAST SAS BIC');
        
        }
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
          
                'nombre' => $this->pqr['nombre'],
                'empresa' => $this->pqr['empresa'],
                'telefono' => $this->pqr['telefono'],
                'correo' => $this->emailSolicitante,
                'mensaje_pqr' => $this->pqr['mensaje'],
                'estado' => 'pendiente',
     
       ];

    }
}

