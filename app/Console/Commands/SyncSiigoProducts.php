<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SiigoService;

class SyncSiigoProducts extends Command
{
    /**
     * Nombre y firma del comando (php artisan ...).
     *
     * @var string
     */
    protected $signature = 'siigo:sync-products {--page= : Página específica de Siigo}';

    /**
     * Descripción del comando.
     *
     * @var string
     */
    protected $description = 'Sincroniza productos desde Siigo hacia la base local';

    /**
     * Ejecutar el comando.
     */
    public function handle(SiigoService $siigoService)
    {
        $this->info('Iniciando sincronización de productos desde Siigo...');

        $params = [];
        if ($this->option('page')) {
            $params['page'] = $this->option('page');
            $this->warn("⚠️ Se sincronizará solo la página {$params['page']}");
        }

        $resultado = $siigoService->sincronizarProductosDesdeSiigo($params);

        $this->info("✅ Sincronización completada");
        $this->line(" - Productos creados: {$resultado['productos_guardados']}");
        $this->line(" - Productos actualizados: {$resultado['productos_actualizados']}");
        $this->line(" - Total procesados: {$resultado['total_procesados']}");

        if (!empty($resultado['detalle'])) {
            $this->table(
                ['Acción', 'Código', 'ID Siigo'],
                collect($resultado['detalle'])->map(fn($item) => [
                    $item['accion'],
                    $item['code'] ?? 'N/A',
                    $item['id'] ?? 'N/A',
                ])
            );
        }

        return Command::SUCCESS;
    }
}

