<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SiigoService;

class RenovarTokenSiigo extends Command
{
    protected $signature = 'siigo:renovar-token';
    protected $description = 'Renueva manualmente el token de acceso de Siigo';

    public function handle(SiigoService $siigoService)
    {
        $this->info('Renovando token de Siigo...');

        $nuevoToken = $siigoService->renovarTokenManualmente();

        if ($nuevoToken) {
            $this->info('✅ Token renovado y guardado correctamente.');
        } else {
            $this->error('❌ No se pudo renovar el token.');
        }
    }
}
