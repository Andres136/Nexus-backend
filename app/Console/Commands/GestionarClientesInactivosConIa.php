<?php

namespace App\Console\Commands;

use App\Services\Crm\ChatbotGestionClientesIaService;
use Illuminate\Console\Command;

class GestionarClientesInactivosConIa extends Command
{
    protected $signature = 'app:gestionar-clientes-inactivos-ia';
    protected $description = 'Contacta semanalmente hasta 10 clientes con 30 días sin gestión mediante correos redactados por IA';

    public function handle(ChatbotGestionClientesIaService $service): int
    {
        $clientes = $service->clientesElegibles();
        $enviados = 0;
        $fallidos = 0;

        foreach ($clientes as $cliente) {
            $resultado = $service->gestionar($cliente);
            $resultado['enviado'] ? $enviados++ : $fallidos++;
            $detalle = $resultado['enviado'] ? 'enviado' : 'fallido - ' . ($resultado['error'] ?? 'error desconocido');
            $this->line("Cliente #{$cliente->id}: {$detalle}");
        }

        $this->info("Gestión IA terminada. Seleccionados: {$clientes->count()}, enviados: {$enviados}, fallidos: {$fallidos}.");
        return $fallidos > 0 ? self::FAILURE : self::SUCCESS;
    }
}
