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
        $tareasVencidas = collect([$this->tarea]);

        $esVencida = $hoy->greaterThan($fechaFin);
        $subject = $esVencida 
            ? '🚨 TAREA VENCIDA - Acción Inmediata Requerida'
            : '⚠️ TAREA POR VENCER - Atención Requerida';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.tarea-vencida', [
                'usuario' => $notifiable,
                'tarea' => $this->tarea,
                'tareasVencidas' => $tareasVencidas,
                'esVencida' => $esVencida,
                'url' => config('app.frontend_url') . '/auth/crm/tareas/' . $this->tarea->id
            ]);
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
