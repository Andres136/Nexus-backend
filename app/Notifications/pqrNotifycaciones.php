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
                ->line('Tipo: ' . $this->pqr['tipo'])
                ->line('Mensaje: ' . $this->pqr['mensaje'])
                ->line('Nombre : ' . $this->pqr['nombre'])
                ->line('Empresa: ' . $this->pqr['empresa'])
                ->line('Teléfono : ' . $this->pqr['telefono'])
                ->line('Correo del solicitante: ' . $this->emailSolicitante)
                ->line('Por favor, revisa la solicitud.');
        } else {
            return (new MailMessage)
                ->subject('Confirmación de PQR')
                ->line('Hemos recibido tu solicitud con los siguientes detalles:')
                ->line('Tipo: ' . $this->pqr['tipo'])
                ->line('Mensaje: ' . $this->pqr['mensaje'])
                ->line('Nos pondremos en contacto contigo pronto.')
                ->line('Gracias por comunicarte con nosotros.');
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
           'tipo' => $this->pqr['tipo'],
           'mensaje' => $this->pqr['mensaje'],
           'email' => $this->emailSolicitante
       ];

    }
}

