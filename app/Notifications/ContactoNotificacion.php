<?php

// app/Notifications/ContactoNotificacion.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ContactoNotificacion extends Notification
{
    use Queueable;

    protected $contacto;
    protected $tipo;
    protected $emailDestino;

    public function __construct(array $contacto, string $tipo, string $emailDestino)
    {
        $this->contacto = $contacto;
        $this->tipo = $tipo;
        $this->emailDestino = $emailDestino;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->tipo === 'admin') {
            return (new MailMessage)
                ->subject('Nuevo mensaje desde la web')
                ->view('notifications.contacto-admin', [
                    'usuario' => $notifiable,
                    'contacto' => $this->contacto
                ]);






                
        } else {
            return (new MailMessage)
                ->subject('Hemos recibido tu mensaje')
                ->view('notifications.contacto-usuario', [
                    'usuario' => $notifiable,
                    'contacto' => $this->contacto
                ]);
        }
    }
 public function toArray(object $notifiable): array
    {
        return [
            'contacto' => $this->contacto,
            'tipo' => $this->tipo,
        ];
    }
}
   


    