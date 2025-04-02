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
                ->line('Se ha recibido un nuevo mensaje desde el formulario de contacto:')
                ->line('Nombre: ' . $this->contacto['nombre'])
                ->line('Empresa: ' . $this->contacto['empresa'])
                ->line('Teléfono: ' . $this->contacto['telefono'])
                ->line('Correo del remitente: ' . $this->contacto['email'])
                ->line('Mensaje:')
                ->line($this->contacto['mensaje'])
                ->replyTo($this->contacto['email'], $this->contacto['nombre']);
        } else {
            return (new MailMessage)
                ->subject('Hemos recibido tu mensaje')
                ->line('Hola ' . $this->contacto['nombre'] . ',')
                ->line('Gracias por contactarte con nosotros. Hemos recibido tu mensaje y nos pondremos en contacto contigo pronto.')
                ->line('Resumen del mensaje enviado:')
                ->line('Empresa: ' . $this->contacto['empresa'])
                ->line('Teléfono: ' . $this->contacto['telefono'])
                ->line('Mensaje:')
                ->line($this->contacto['mensaje'])
                ->line('Saludos,')
                ->line('Equipo de SETASPLAST');
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
   


    