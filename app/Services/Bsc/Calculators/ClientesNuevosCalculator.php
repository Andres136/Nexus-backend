<?php

namespace App\Services\Bsc\Calculators;

use App\EstadoEnum;
use App\Services\Bsc\Contracts\IndicadorCalculator;
use App\Services\Bsc\Support\PeriodoHelper;
use Illuminate\Support\Facades\DB;

/**
 * Tasa de clientes nuevos:
 *   clientes registrados en el mes / total de clientes activos * 100
 *
 * Perspectiva Cliente.
 */
class ClientesNuevosCalculator implements IndicadorCalculator
{
    use PeriodoHelper;

    public function key(): string
    {
        return 'crm.clientes_nuevos';
    }

    public function label(): string
    {
        return 'Tasa de clientes nuevos';
    }

    public function unidad(): string
    {
        return '%';
    }

    public function calcular(string $periodo): array
    {
        [$ini, $fin] = $this->rangoMes($periodo);
        $inactivo = EstadoEnum::INACTIVO->value;

        $nuevos = (int) DB::table('clientes')
            ->where('estado_id', '!=', $inactivo)
            ->whereBetween('created_at', [$ini, $fin])
            ->count();

        $total = (int) DB::table('clientes')
            ->where('estado_id', '!=', $inactivo)
            ->where('created_at', '<=', $fin)
            ->count();

        $valor = $total > 0 ? round(($nuevos / $total) * 100, 2) : 0.0;

        return [
            'valor'       => $valor,
            'numerador'   => $nuevos,
            'denominador' => $total,
            'detalle'     => "{$nuevos} clientes nuevos de {$total} activos",
        ];
    }
}
