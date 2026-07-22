<?php

namespace App\Notifications\Crm;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FacturaCarteraNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $factura;
    protected $tipo;
    protected $usuarioCreadorOc;

    public function __construct($factura, $tipo, $usuarioCreadorOc = null)
    {
        $this->factura = $factura;
        $this->tipo = $tipo;
        $this->usuarioCreadorOc = $usuarioCreadorOc;
    }

    /**
     * Link de WhatsApp hacia el usuario que creó la Orden de Compra que
     * disparó este aviso (null si no hay usuario o no tiene teléfono).
     */
    private function whatsappUrl(): ?string
    {
        if (!$this->usuarioCreadorOc || empty($this->usuarioCreadorOc->telefono)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->usuarioCreadorOc->telefono);
        if (empty($digits)) {
            return null;
        }
        if (strlen($digits) === 10) {
            $digits = '57' . $digits; // Colombia
        }

        $mensaje = rawurlencode(
            "Hola {$this->usuarioCreadorOc->name}, te contacto por la factura {$this->factura->numero_factura}" .
            (optional($this->factura->cliente)->nombre ? " del cliente {$this->factura->cliente->nombre}." : ".")
        );

        return "https://wa.me/{$digits}?text={$mensaje}";
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
        ->subject('Factura en cartera')
        ->view('notifications.factura-cartera-' . $this->tipo, [
            'factura' => $this->factura,
            'url' => config('app.frontend_url') . '/auth/crm/gestion-cartera/' . $this->factura->id,
            'whatsappUrl' => $this->whatsappUrl(),
            'usuarioCreadorOc' => $this->usuarioCreadorOc,
        ]);
}

    public function toDatabase(object $notifiable): array
    {
        $this->factura->loadMissing('cliente');

        $mensaje = $this->tipo === 'vencida'
            ? 'Factura vencida'
            : 'Factura próxima a vencer';

        return [
            'mensaje' => $mensaje,
            'gestion_cartera_id' => $this->factura->id,
            'numero_factura' => $this->factura->numero_factura,
            'cliente' => optional($this->factura->cliente)->nombre,
            'fecha_vencimiento' => $this->factura->fecha_vencimiento,
            'tipo' => $this->tipo,
            'url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/auth/crm/gestion-cartera/' . $this->factura->id,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
