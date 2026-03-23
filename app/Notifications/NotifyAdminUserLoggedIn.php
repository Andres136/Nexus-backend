<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class NotifyAdminUserLoggedIn extends Notification
{
    use Queueable;


    private $name;
    private $ipAddress;
    private $device;

    /**
     * Create a new notification instance.
     */




    public function __construct( $name, $ipAddress, $device)
    {

        $this->name = $name;
        $this->ipAddress = $ipAddress;
        $this->device = $device;
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
        return (new MailMessage)
            ->subject('Inicio de sesión - ' . $this->name)
            ->view('notifications.login-admin-limpia', [
                'usuario' => $notifiable,
                'nombreUsuario' => $this->name,
                'ipAddress' => $this->ipAddress,
                'device' => $this->device,

            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable)
    {
        return [
            'mensaje' => 'El usuario "' . $this->name . '" ha iniciado sesión en el sistema.',
            'ipAddress' => $this->ipAddress,
            'device' => $this->device,

        ];
    }
    
}
