<?php

namespace App\Console\Commands;

use App\Services\Crm\KpiService;
use Illuminate\Console\Command;

class GuardarSnapshotCarteraMensual extends Command
{
    protected $signature = 'app:guardar-snapshot-cartera-mensual';

    protected $description = 'Congela el % de gestión sobre cartera vencida del mes en curso, para que el dashboard no lo recalcule en vivo sobre meses ya cerrados';

    public function handle(KpiService $kpiService): void
    {
        $snapshot = $kpiService->guardarSnapshotCarteraMensual();

        $this->info("Snapshot cartera {$snapshot->anio}-{$snapshot->mes}: {$snapshot->cartera_gestionadas}/{$snapshot->cartera_vencidas} ({$snapshot->cartera_pct_gestion}%)");
    }
}
