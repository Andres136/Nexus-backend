<?php

namespace App\Notifications;

use App\Models\Nomina\WorkSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RecordatorioMiDiaNotificacion extends Notification
{
    use Queueable;

    public function __construct(private readonly WorkSession $workSession)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $horaEntrada = optional($this->workSession->hora_entrada)->format('H:i');

        return [
            'mensaje' => $horaEntrada
                ? "Marcaste entrada a las {$horaEntrada} y aún no has registrado ninguna actividad en Mi Día."
                : 'Aún no has registrado ninguna actividad en Mi Día.',
            'url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/auth/mi-dia',
        ];
    }
}
