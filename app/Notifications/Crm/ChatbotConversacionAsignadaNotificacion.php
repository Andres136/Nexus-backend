<?php

namespace App\Notifications\Crm;

use App\Models\Crm\ChatbotConversacion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChatbotConversacionAsignadaNotificacion extends Notification
{
    use Queueable;

    public function __construct(protected ChatbotConversacion $conversacion)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.frontend_url', config('app.url')), '/')
            . '/auth/crm/chatbot/' . $this->conversacion->id;
        $mensajes = $this->conversacion->mensajes()
            ->reorder('id', 'desc')
            ->limit(4)
            ->get()
            ->reverse()
            ->values();

        return (new MailMessage)
            ->subject('💬 Nueva conversación asignada - ' . ($this->conversacion->nombre_lead ?? 'Visitante web'))
            ->view('emails.chatbot-conversacion-asignada', [
                'usuario' => $notifiable,
                'conversacion' => $this->conversacion,
                'mensajes' => $mensajes,
                'url' => $url,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'chatbot_asignado',
            'conversacion_id' => $this->conversacion->id,
            'nombre_lead' => $this->conversacion->nombre_lead,
        ];
    }
}
