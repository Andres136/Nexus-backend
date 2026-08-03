<?php

namespace App\Services\Crm;

use App\Models\Crm\ChatbotCorreoCliente;
use App\Models\Crm\Cotizacion;
use App\Models\Crm\SeguimientoCliente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class CotizacionEnvioClienteService
{
    public function enviar(Cotizacion $cotizacion): array
    {
        $cotizacion->loadMissing(['cliente', 'detalles', 'user', 'empresaReal']);

        if ($cotizacion->enviada_cliente_at) {
            return ['enviado' => true, 'mensaje' => 'La cotización ya había sido enviada al cliente.'];
        }
        if (!$cotizacion->cliente?->email || !filter_var($cotizacion->cliente->email, FILTER_VALIDATE_EMAIL)) {
            return $this->fallar($cotizacion, 'El cliente no tiene un correo válido.');
        }

        $logo = $cotizacion->empresaReal?->logo
            ? storage_path('app/public/' . $cotizacion->empresaReal->logo)
            : public_path('images/SETAS.png');
        $pdf = Pdf::loadView('pdf.cotizacion', compact('cotizacion', 'logo'))->output();
        $asunto = "Cotización #{$cotizacion->id} - " . ($cotizacion->empresaReal?->nombre ?? $cotizacion->empresa);
        $registro = ChatbotCorreoCliente::create([
            'cliente_id' => $cotizacion->cliente_id,
            'user_id' => $cotizacion->aprobado_por ?: $cotizacion->responsable_id,
            'destinatario' => $cotizacion->cliente->email,
            'asunto' => $asunto,
            'mensaje' => 'Envío automático de cotización aprobada desde Nexus.',
            'estado' => 'procesando',
        ]);

        try {
            Mail::send('emails.cotizacion-aprobada-cliente', [
                'cotizacion' => $cotizacion,
                'cliente' => $cotizacion->cliente,
                'empresa' => $cotizacion->empresaReal,
            ], function ($mail) use ($cotizacion, $asunto, $pdf) {
                $mail->to($cotizacion->cliente->email, $cotizacion->cliente->nombre)
                    ->subject($asunto)
                    ->attachData($pdf, "Cotizacion_{$cotizacion->id}.pdf", ['mime' => 'application/pdf']);
            });

            $momento = now();
            $registro->update(['estado' => 'enviado', 'enviado_at' => $momento]);
            $cotizacion->update(['enviada_cliente_at' => $momento, 'envio_cliente_error' => null]);
            SeguimientoCliente::create([
                'cliente_id' => $cotizacion->cliente_id,
                'user_id' => $cotizacion->aprobado_por ?: $cotizacion->responsable_id,
                'tipo_contacto' => 'Email',
                'estado' => 'Cotización enviada',
                'comentario' => "Cotización #{$cotizacion->id} aprobada y enviada automáticamente por correo.",
            ]);

            return ['enviado' => true, 'mensaje' => 'Cotización aprobada y enviada al cliente.'];
        } catch (\Throwable $e) {
            $registro->update(['estado' => 'fallido', 'error' => $e->getMessage()]);
            report($e);
            return $this->fallar($cotizacion, $e->getMessage());
        }
    }

    private function fallar(Cotizacion $cotizacion, string $error): array
    {
        $cotizacion->update(['envio_cliente_error' => $error]);
        return ['enviado' => false, 'mensaje' => 'La cotización fue aprobada, pero no se pudo enviar al cliente.', 'error' => $error];
    }
}
