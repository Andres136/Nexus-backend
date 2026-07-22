<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class RecordatorioCarteraClienteMail extends Mailable
{
    public function __construct(
        public $cliente,
        public array $resumen
    ) {
    }

    public function build()
    {
        $tieneVencida = !empty($this->resumen['tiene_vencida']);
        $tieneProxima = !empty($this->resumen['tiene_proxima']);

        $asunto = match (true) {
            $tieneVencida && $tieneProxima => '⚠️ Recordatorio: tienes facturas vencidas y próximas a vencer',
            $tieneVencida => '⚠️ Recordatorio: tienes cartera vencida',
            default => '⚠️ Recordatorio: cartera próxima a vencer',
        };

        return $this
            ->subject($asunto)
            ->view('emails.recordatorio-cartera-cliente')
            ->with([
                'cliente' => $this->cliente,
                'resumen' => $this->resumen,
            ]);
    }
}
