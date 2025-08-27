<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NuevaTareaAsignada extends Notification
{
    use Queueable;
    protected $tarea;

    /**
     * Create a new notification instance.
     */
    public function __construct($tarea)
    {
        //
        $this->tarea = $tarea;
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
            ->subject('✅ Nueva Tarea Asignada - ' . ($this->tarea->titulo ?? $this->tarea->nombre ?? 'Tarea Importante'))
            ->view('emails.nueva-tarea', [
                'usuario' => $notifiable,
                'tarea' => $this->tarea,
                'url' => config('app.frontend_url') . '/auth/crm/tareas/' . $this->tarea->id
            ]);
    }
    

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
   public  function toDatabase(object $notifiable): array
    {
        return [
            'tarea' => $this->tarea
        ];
    }
}
