<?php

namespace App\Services\Bsc\Calculators;

use App\Services\Bsc\Contracts\IndicadorCalculator;
use App\Services\Bsc\Support\PeriodoHelper;
use Illuminate\Support\Facades\DB;

/**
 * Tasa de cumplimiento de metas:
 *   ventas del mes / meta del mes * 100
 *
 * Fuente: orden__compras.valor_total y meta_mensuals (anio, mes, valor_meta).
 * Perspectiva Aprendizaje / seguimiento estratégico.
 */
class CumplimientoMetasCalculator implements IndicadorCalculator
{
    use PeriodoHelper;

    public function key(): string
    {
        return 'crm.cumplimiento_metas';
    }

    public function label(): string
    {
        return 'Tasa de cumplimiento de metas de ventas';
    }

    public function unidad(): string
    {
        return '%';
    }

    public function calcular(string $periodo): array
    {
        [$ini, $fin] = $this->rangoMes($periodo);
        [$anio, $mes] = array_map('intval', explode('-', $periodo));

        $ventas = (float) DB::table('orden__compras')
            ->whereBetween('created_at', [$ini, $fin])
            ->sum('valor_total');

        $meta = (float) DB::table('meta_mensuals')
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->sum('valor_meta');

        $valor = $meta > 0 ? round(($ventas / $meta) * 100, 2) : 0.0;

        return [
            'valor'       => $valor,
            'numerador'   => round($ventas, 2),
            'denominador' => round($meta, 2),
            'detalle'     => "Ventas " . number_format($ventas, 0)
                . " / meta " . number_format($meta, 0),
        ];
    }
}
