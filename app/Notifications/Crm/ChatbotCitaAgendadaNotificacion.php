<?php

namespace App\Notifications\Crm;

use App\Models\Crm\ChatbotCita;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChatbotCitaAgendadaNotificacion extends Notification
{
    use Queueable;

    public function __construct(protected ChatbotCita $cita)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📅 Nueva cita agendada por el chatbot')
            ->line('El chatbot agendó una cita.')
            ->line('Con: ' . $this->cita->nombre_lead . ($this->cita->empresa_lead ? ' (' . $this->cita->empresa_lead . ')' : ''))
            ->line('Fecha: ' . $this->cita->fecha_inicio->format('d/m/Y H:i'))
            ->action('Ver calendario', config('app.frontend_url') . '/auth/crm/chatbot/citas');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo' => 'chatbot_cita_agendada',
            'cita_id' => $this->cita->id,
            'nombre_lead' => $this->cita->nombre_lead,
            'fecha_inicio' => $this->cita->fecha_inicio,
        ];
    }
}
