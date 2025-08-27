<?php

namespace App\Notifications;

use App\Models\Crm\OrdenDeTrabajo;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class OrdenTrabajoCreada extends Notification
{
    use Queueable; // No encola si NO implementas ShouldQueue

    private OrdenDeTrabajo $ot;

    public function __construct(OrdenDeTrabajo $ordenTrabajo)
    {
        // Carga defensiva
        $ordenTrabajo->loadMissing(['ordenCompra.sede', 'cliente']);
        $this->ot = $ordenTrabajo;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tz = config('app.timezone', 'America/Bogota');

        $clienteNombre = optional($this->ot->cliente)->nombre ?? 'No especificado';
        $sedeNombre    = optional(optional($this->ot->ordenCompra)->sede)->nombre ?? 'Sede no asignada';
        $fechaLegible  = $this->ot->fecha_entrega
            ? Carbon::parse($this->ot->fecha_entrega, 'UTC')->setTimezone($tz)->format('d/m/Y')
            : 'Por confirmar';

        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        $url  = $base . '/auth/crm/ordenes-trabajo/' . $this->ot->id;

        $view = 'emails.orden-trabajo-creada-limpia';

        if (!View::exists($view)) {
            Log::error("Vista no encontrada: {$view}");
            return (new MailMessage)
                ->subject('🔧 Nueva Orden de Trabajo #' . str_pad($this->ot->id, 6, '0', STR_PAD_LEFT))
                ->line("Cliente: {$clienteNombre}")
                ->line("Sede: {$sedeNombre}")
                ->line("Fecha de entrega: {$fechaLegible}")
                ->action('Ver Orden de Trabajo', $url);
        }

        Log::info("Renderizando {$view} => " . View::getFinder()->find($view));

        return (new MailMessage)
            ->subject('🔧 Nueva Orden de Trabajo #' . str_pad($this->ot->id, 6, '0', STR_PAD_LEFT))
            ->view($view, [
                'usuario'         => $notifiable,
                'ordenTrabajo'    => $this->ot,            // por si la vista requiere más campos
                'cliente_nombre'  => $clienteNombre,       // mínimos acordados
                'sede_nombre'     => $sedeNombre,
                'fecha_entrega'   => $fechaLegible,
                'observaciones'   => $this->ot->observaciones ?? null,
                'url'             => $url,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'mensaje'          => 'Se generó una Orden de Trabajo',
            'orden_trabajo_id' => $this->ot->id,
            'fecha_entrega'    => $this->ot->fecha_entrega, // cruda (ISO/DB)
            'cliente'          => optional($this->ot->cliente)->nombre,
            'sede'             => optional(optional($this->ot->ordenCompra)->sede)->nombre,
            'url'              => rtrim(config('app.frontend_url', config('app.url')), '/') . '/auth/crm/ordenes-trabajo/' . $this->ot->id,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
