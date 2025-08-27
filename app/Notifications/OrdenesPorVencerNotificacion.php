<?php

namespace App\Notifications;

use App\Models\Crm\Orden_Compra;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class OrdenesPorVencerNotificacion extends Notification
{
    use Queueable;

    protected $ordenCompra;

    public function __construct(Orden_Compra $ordenCompra)
    {
        $this->ordenCompra = $ordenCompra;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $hoy = Carbon::now();
        $fechaEntrega = Carbon::parse($this->ordenCompra->fecha_entrega);

        // Si la orden ya venció, cambia el mensaje
        $mensaje = $hoy->greaterThan($fechaEntrega)
            ? '⚠️ ¡Esta orden de compra ya ha vencido! Por favor, toma acción de inmediato.'
            : '🔔 ¡Orden de compra por vencer! Toma acción antes de la fecha límite.';

        return (new MailMessage)
            ->subject('📢 Estado de Orden de Compra')
            ->view('emails.orden-urgente', [
                'usuario' => $notifiable,
                'ordenCompra' => $this->ordenCompra,
                'mensaje' => $mensaje,
                'url' => config('app.frontend_url') . '/auth/crm/ordenes-compra/' . $this->ordenCompra->id
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'orden_id' => $this->ordenCompra->id,
            "nombre" => $this->ordenCompra->nombre,
            'fecha_entrega' => $this->ordenCompra->fecha_entrega,
            'cliente' => $this->ordenCompra->cliente->nombre,
            'ubicacion_entrega' => $this->ordenCompra->ubicacion_entrega,
            'mensaje' => Carbon::now()->greaterThan($this->ordenCompra->fecha_entrega)
                ? '⚠️ ¡Orden vencida! Requiere atención inmediata.'
                : '🔔 ¡Orden por vencer! Actúa antes de la fecha límite.'
        ];
    }
}
