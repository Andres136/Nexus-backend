<?php

namespace App\Services\Bsc\Calculators;

use App\Services\Bsc\Contracts\IndicadorCalculator;
use App\Services\Bsc\Support\PeriodoHelper;
use Illuminate\Support\Facades\DB;

/**
 * Productos no conformes del mes: conteo de reportes en productos_no_conformes
 * con fecha_reporte dentro del periodo.
 *
 * El BSC pide un %, pero hoy no hay un "total de OPs producidas" confiable en
 * el sistema, así que Fase 1 entrega el conteo absoluto (tipo_meta = menor).
 * Cuando exista el denominador real se cambia solo este calculator.
 * Perspectiva Procesos.
 */
class ProductosNoConformesCalculator implements IndicadorCalculator
{
    use PeriodoHelper;

    public function key(): string
    {
        return 'hseq.productos_no_conformes';
    }

    public function label(): string
    {
        return 'Productos no conformes reportados (conteo)';
    }

    public function unidad(): string
    {
        return 'ratio';
    }

    public function calcular(string $periodo): array
    {
        [$ini, $fin] = $this->rangoMes($periodo);

        $total = (int) DB::table('productos_no_conformes')
            ->whereBetween('fecha_reporte', [$ini->toDateString(), $fin->toDateString()])
            ->count();

        $cliente = (int) DB::table('productos_no_conformes')
            ->whereBetween('fecha_reporte', [$ini->toDateString(), $fin->toDateString()])
            ->where('origen', 'cliente')
            ->count();

        return [
            'valor'       => (float) $total,
            'numerador'   => $total,
            'denominador' => null,
            'detalle'     => "{$total} no conformes ({$cliente} de origen cliente)",
        ];
    }
}
