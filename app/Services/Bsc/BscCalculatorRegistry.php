<?php

namespace App\Services\Bsc;

use App\Services\Bsc\Calculators\ClientesNuevosCalculator;
use App\Services\Bsc\Calculators\ConversionClientesCalculator;
use App\Services\Bsc\Calculators\CrecimientoVentasCalculator;
use App\Services\Bsc\Calculators\CumplimientoMetasCalculator;
use App\Services\Bsc\Calculators\ProductosNoConformesCalculator;
use App\Services\Bsc\Contracts\IndicadorCalculator;

/**
 * Catálogo de calculators automáticos disponibles. Para agregar un indicador
 * automático nuevo: crear la clase en Calculators/ y registrarla aquí.
 */
class BscCalculatorRegistry
{
    /** @var array<string, class-string<IndicadorCalculator>> */
    private const CALCULATORS = [
        'crm.crecimiento_ventas'      => CrecimientoVentasCalculator::class,
        'crm.cumplimiento_metas'      => CumplimientoMetasCalculator::class,
        'crm.conversion_clientes'     => ConversionClientesCalculator::class,
        'crm.clientes_nuevos'         => ClientesNuevosCalculator::class,
        'hseq.productos_no_conformes' => ProductosNoConformesCalculator::class,
    ];

    public function has(string $key): bool
    {
        return isset(self::CALCULATORS[$key]);
    }

    public function get(string $key): ?IndicadorCalculator
    {
        if (!$this->has($key)) {
            return null;
        }

        return app(self::CALCULATORS[$key]);
    }

    /** @return IndicadorCalculator[] */
    public function all(): array
    {
        return array_map(fn ($class) => app($class), array_values(self::CALCULATORS));
    }

    /** Lista para la pantalla de clasificación: [key => label]. */
    public function opciones(): array
    {
        $out = [];
        foreach ($this->all() as $calc) {
            $out[] = [
                'key'    => $calc->key(),
                'label'  => $calc->label(),
                'unidad' => $calc->unidad(),
            ];
        }

        return $out;
    }
}
