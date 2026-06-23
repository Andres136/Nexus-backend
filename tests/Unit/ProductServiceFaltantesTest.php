<?php

namespace Tests\Unit;

use App\Services\ProductService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use stdClass;

class ProductServiceFaltantesTest extends TestCase
{
    public function test_recalcula_kilos_pendientes_segun_unidades_enviadas(): void
    {
        $detalle = new stdClass();
        $detalle->cantidad_requerida_kg = 100;
        $detalle->cantidad = 200;
        $detalle->cantidad_enviada = 50;

        $method = new ReflectionMethod(ProductService::class, 'calcularCantidadPendienteKg');
        $method->setAccessible(true);

        $resultado = $method->invoke(new ProductService(), $detalle);

        $this->assertSame(75.0, $resultado);
    }

    public function test_no_mezcla_unidades_con_stock_si_no_hay_kilos_requeridos(): void
    {
        $detalle = new stdClass();
        $detalle->cantidad_requerida_kg = 0;
        $detalle->cantidad = 123;
        $detalle->cantidad_enviada = 0;

        $method = new ReflectionMethod(ProductService::class, 'calcularCantidadPendienteKg');
        $method->setAccessible(true);

        $resultado = $method->invoke(new ProductService(), $detalle);

        $this->assertSame(0.0, $resultado);
    }
}
