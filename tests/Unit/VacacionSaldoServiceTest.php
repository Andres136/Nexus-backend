<?php

namespace Tests\Unit;

use App\Services\Nomina\VacacionSaldoService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class VacacionSaldoServiceTest extends TestCase
{
    public function test_usa_anio_laboral_de_360_dias(): void
    {
        $service = new VacacionSaldoService();

        $dias = $service->diasComerciales(
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-12-31')
        );

        $this->assertSame(360, $dias);
        $this->assertEquals(15.0, $dias * 15 / 360);
    }

    public function test_un_mes_completo_equivale_a_30_dias(): void
    {
        $service = new VacacionSaldoService();

        $this->assertSame(
            30,
            $service->diasComerciales(
                Carbon::parse('2026-02-01'),
                Carbon::parse('2026-02-28')
            )
        );
    }
}
