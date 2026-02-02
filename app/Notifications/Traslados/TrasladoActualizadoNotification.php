<?php

namespace App\Notifications\Traslados;

use App\Models\Traslados\Traslado_Bodega;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrasladoActualizadoNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public Traslado_Bodega $traslado;
    public function __construct(Traslado_Bodega $traslado)
    {
        $this->traslado = $traslado;
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
        $traslado =$this->traslado->load(
            [
                'detalles.producto',
                'bodegaOrigen',
                'bodegaDestino',
                'creador'
            ]
        );



       // URLs (ajusta rutas)
        $aprobarUrl = route('email.traslados.aprobar', ['traslado' => $traslado->id, 'user' => $notifiable->id]);
        $rechazarUrl = route('email.traslados.rechazar', ['traslado' => $traslado->id, 'user' => $notifiable->id]);

        return (new MailMessage)
            ->subject("Traslado pendiente de aprobación (Bodega) - {$traslado->codigo}")
            ->view('emails.traslados.pendiente_bodega', [
                'traslado'   => $traslado,
                'aprobarUrl' => $aprobarUrl,
                'rechazarUrl'=> $rechazarUrl,
                'usuario'=>$notifiable,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
