<?php

namespace App\Services\Bsc\Calculators;

use App\Services\Bsc\Contracts\IndicadorCalculator;
use App\Services\Bsc\Support\PeriodoHelper;
use Illuminate\Support\Facades\DB;

/**
 * Tasa de conversión de clientes:
 *   N° de órdenes / N° de cotizaciones * 100  (en el mes)
 *
 * Mismo criterio que ComercialDashboardService.conversion_pct.
 * Perspectiva Cliente.
 */
class ConversionClientesCalculator implements IndicadorCalculator
{
    use PeriodoHelper;

    public function key(): string
    {
        return 'crm.conversion_clientes';
    }

    public function label(): string
    {
        return 'Tasa de conversión de clientes (órdenes / cotizaciones)';
    }

    public function unidad(): string
    {
        return '%';
    }

    public function calcular(string $periodo): array
    {
        [$ini, $fin] = $this->rangoMes($periodo);

        $ordenes = (int) DB::table('orden__compras')
            ->whereBetween('created_at', [$ini, $fin])
            ->count();

        $cotizaciones = (int) DB::table('cotizaciones')
            ->whereBetween('created_at', [$ini, $fin])
            ->count();

        $valor = $cotizaciones > 0 ? round(($ordenes / $cotizaciones) * 100, 2) : 0.0;

        return [
            'valor'       => $valor,
            'numerador'   => $ordenes,
            'denominador' => $cotizaciones,
            'detalle'     => "{$ordenes} órdenes / {$cotizaciones} cotizaciones",
        ];
    }
}
