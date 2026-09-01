<?php

namespace App\Console\Commands;

use App\Services\Bsc\BscSnapshotService;
use Illuminate\Console\Command;

class BscSnapshot extends Command
{
    protected $signature = 'bsc:snapshot {--periodo= : Mes YYYY-MM (por defecto el mes en curso)}';

    protected $description = 'Calcula y congela los indicadores automáticos del Cuadro de Mando Integral para el periodo dado';

    public function handle(BscSnapshotService $service): int
    {
        $periodo = $this->option('periodo');
        $resultados = $service->ejecutar($periodo);

        if (empty($resultados)) {
            $this->warn('No hay indicadores con calculo_key asignado.');

            return self::SUCCESS;
        }

        $this->table(
            ['Indicador', 'calculo_key', 'Valor'],
            array_map(fn ($r) => [$r['indicador'], $r['key'], $r['valor']], $resultados)
        );

        return self::SUCCESS;
    }
}
