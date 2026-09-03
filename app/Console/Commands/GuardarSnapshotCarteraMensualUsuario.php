<?php

namespace App\Console\Commands;

use App\Services\Crm\ComercialDashboardService;
use Illuminate\Console\Command;

class GuardarSnapshotCarteraMensualUsuario extends Command
{
    protected $signature = 'app:guardar-snapshot-cartera-mensual-usuario';

    protected $description = 'Congela el % de gestión sobre cartera vencida del mes en curso por vendedor, para que el comparativo por usuario no lo recalcule en vivo sobre meses ya cerrados';

    public function handle(ComercialDashboardService $service): void
    {
        $snapshots = $service->guardarSnapshotCarteraMensualPorUsuario();

        $this->info("Snapshot cartera por usuario guardado: {$snapshots->count()} vendedor(es).");
    }
}
