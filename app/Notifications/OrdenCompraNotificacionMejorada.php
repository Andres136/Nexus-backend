<?php

namespace App\Notifications;

use App\Models\Crm\Orden_Compra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class OrdenCompraNotificacionMejorada extends Notification
{
    use Queueable;

    public $ordenCompra;

    /**
     * Create a new notification instance.
     */
    public function __construct(Orden_Compra $ordenCompra)
    {
        $this->ordenCompra = $ordenCompra;
    }

    /**
     * Get the notification's delivery channels.
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
        Log::info("📧 Enviando correo profesional a: " . $notifiable->email);

        return (new MailMessage)
            ->subject('🛒 Nueva Orden de Compra #' . str_pad($this->ordenCompra->id, 6, '0', STR_PAD_LEFT) . ' - Acción Requerida')
            ->view('emails.orden-compra-creada', [
                'usuario' => $notifiable,
                'orden' => $this->ordenCompra,
                'url' => config('app.frontend_url') . '/auth/crm/ordenes/' . $this->ordenCompra->id
            ]);
    }

    /**
     * Notificación para la base de datos con más detalles
     */
    public function toDatabase($notifiable)
    {
        return [
            'tipo' => 'orden_compra_creada',
            'orden_compra_id' => $this->ordenCompra->id ?? 'Sin ID',
            'numero_orden' => str_pad($this->ordenCompra->id, 6, '0', STR_PAD_LEFT),
            'usuario_nombre' => $notifiable->name,
            'usuario_email' => $notifiable->email,
            'cliente' => optional($this->ordenCompra->cliente)->nombre ?? 'Cliente no definido',
            'fecha_entrega' => $this->ordenCompra->fecha_entrega ?? 'No especificada',
            'ubicacion_entrega' => $this->ordenCompra->ubicacion_entrega ?? 'No especificada',
            'valor_total' => $this->ordenCompra->valor_total ?? 0,
            'valor_formateado' => '$' . number_format($this->ordenCompra->valor_total ?? 0, 0, ',', '.'),
            'urgente' => $this->esUrgente(),
            'prioridad' => $this->calcularPrioridad(),
            'mensaje' => 'Nueva orden de compra creada - Requiere atención',
            'fecha_creacion' => now()->format('Y-m-d H:i:s'),
            'url_accion' => config('app.frontend_url') . '/auth/crm/ordenes/' . $this->ordenCompra->id
        ];
    }

    /**
     * Determinar si la orden es urgente
     */
    private function esUrgente(): bool
    {
        if (!$this->ordenCompra->fecha_entrega) {
            return false;
        }
        
        $fechaEntrega = \Carbon\Carbon::parse($this->ordenCompra->fecha_entrega);
        $diasHastaEntrega = now()->diffInDays($fechaEntrega, false);
        
        return $diasHastaEntrega <= 3; // Urgente si la entrega es en 3 días o menos
    }

    /**
     * Calcular prioridad de la orden
     */
    private function calcularPrioridad(): string
    {
        $valorTotal = $this->ordenCompra->valor_total ?? 0;
        
        if (!$this->ordenCompra->fecha_entrega) {
            return 'media';
        }
        
        $fechaEntrega = \Carbon\Carbon::parse($this->ordenCompra->fecha_entrega);
        $diasHastaEntrega = now()->diffInDays($fechaEntrega, false);
        
        // Prioridad alta: entrega en menos de 3 días O valor mayor a 5,000,000
        if ($diasHastaEntrega <= 3 || $valorTotal > 5000000) {
            return 'alta';
        }
        
        // Prioridad baja: entrega en más de 15 días Y valor menor a 1,000,000
        if ($diasHastaEntrega > 15 && $valorTotal < 1000000) {
            return 'baja';
        }
        
        return 'media';
    }
}
