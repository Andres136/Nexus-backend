<?php

namespace App\Notifications\Crm;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TareaVencidaNotificacion extends Notification
{
    use Queueable;
    private $tarea;
    /**
     * Create a new notification instance.
     */
    public function __construct($tarea)
    {
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
        $hoy = now();
        $fechaFin = Carbon::parse($this->tarea->fecha_fin);

        $mensaje = $hoy->greaterThan($fechaFin)
            ? '⚠️ ¡Esta tarea ya ha vencido! Por favor, toma acción de inmediato.'
            : '🔔 ¡Tarea por vencer! Toma acción antes de la fecha límite.';
        return (new MailMessage)
            ->subject('📢 Estado de Tarea')
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line($mensaje)
            ->line('Tarea: ' . $this->tarea->nombre)
            ->line('📅 Fecha de entrega: ' . $this->tarea->fecha_fin)
            ->line('📝 Descripción: ' . $this->tarea->descripcion)  
            ->line('Gracias por gestionar tus tareas a tiempo.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    // Guardar en la base de datos
    public function toDatabase($notifiable)
    {
        return [
            'tarea_id' => $this->tarea->id,
            'fecha_fin' => $this->tarea->fecha_fin,
            'nombre' => $this->tarea->nombre,
            'descripcion' => $this->tarea->descripcion,
            'usuario' => $this->tarea->usuario->name,
            'mensaje' => Carbon::now()->greaterThan($this->tarea->fecha_fin)
                ? '⚠️ ¡Tarea vencida! Requiere atención inmediata.'
                : '🔔 ¡Tarea por vencer! Actúa antes de la fecha límite.'
        ];
    }
  
}
