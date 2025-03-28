<?php

namespace App\Console\Commands;

use App\Services\SiigoGlobalService;
use Illuminate\Console\Command;

class RenovarTokenSiigoGlobal extends Command
{
    protected $signature = 'siigo:renovar-token-global';
    protected $description = 'Renueva manualmente el token de acceso de Siigo Global';

    public function handle(SiigoGlobalService $siigo)
    {
        $this->info('🔄 Renovando token de Siigo Global...');

        $nuevoToken = $siigo->renovarTokenManualmente();

        if ($nuevoToken) {
            $this->info('✅ Token renovado correctamente.');
        } else {
            $this->error('❌ No se pudo renovar el token.');
        }
    }
}
