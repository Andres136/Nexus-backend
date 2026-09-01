<?php

namespace App\Services\Bsc\Calculators;

use App\Services\Bsc\Contracts\IndicadorCalculator;
use App\Services\Bsc\Support\PeriodoHelper;
use Illuminate\Support\Facades\DB;

/**
 * Índice de crecimiento de ventas:
 *   (venta periodo actual - venta periodo anterior) / venta periodo anterior * 100
 *
 * Fuente: orden__compras.valor_total por mes (mismo dato que el dashboard
 * comercial). Perspectiva Financiera.
 */
class CrecimientoVentasCalculator implements IndicadorCalculator
{
    use PeriodoHelper;

    public function key(): string
    {
        return 'crm.crecimiento_ventas';
    }

    public function label(): string
    {
        return 'Índice de crecimiento de ventas (Financiera)';
    }

    public function unidad(): string
    {
        return '%';
    }

    public function calcular(string $periodo): array
    {
        [$iniAct, $finAct] = $this->rangoMes($periodo);
        [$iniAnt, $finAnt] = $this->rangoMesAnterior($periodo);

        $ventasActual = (float) DB::table('orden__compras')
            ->whereBetween('created_at', [$iniAct, $finAct])
            ->sum('valor_total');

        $ventasAnterior = (float) DB::table('orden__compras')
            ->whereBetween('created_at', [$iniAnt, $finAnt])
            ->sum('valor_total');

        $valor = $ventasAnterior > 0
            ? round((($ventasActual - $ventasAnterior) / $ventasAnterior) * 100, 2)
            : 0.0;

        return [
            'valor'       => $valor,
            'numerador'   => round($ventasActual - $ventasAnterior, 2),
            'denominador' => round($ventasAnterior, 2),
            'detalle'     => "Ventas {$periodo}: " . number_format($ventasActual, 0)
                . " vs mes anterior: " . number_format($ventasAnterior, 0),
        ];
    }
}
