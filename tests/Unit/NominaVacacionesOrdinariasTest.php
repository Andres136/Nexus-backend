<?php

namespace Tests\Unit;

use App\Models\Nomina\Vacacion;
use App\Services\Nomina\AjusteSalarialContratacionService;
use App\Services\Nomina\ConfiguracionNominaService;
use App\Services\Nomina\NominaService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class NominaVacacionesOrdinariasTest extends TestCase
{
    public function test_vacaciones_desde_el_dia_tres_dejan_dos_dias_de_salario(): void
    {
        $service = new NominaService(
            $this->createMock(AjusteSalarialContratacionService::class),
            $this->createMock(ConfiguracionNominaService::class),
        );
        $vacacion = new Vacacion();
        $vacacion->setRawAttributes([
            'fecha_inicio' => Carbon::parse('2026-06-03'),
            'fecha_fin' => Carbon::parse('2026-06-15'),
            'dias_habiles' => 11,
        ]);

        $method = new ReflectionMethod(NominaService::class, 'diasVacacionEnPeriodo');
        $method->setAccessible(true);

        $diasVacaciones = $method->invoke(
            $service,
            $vacacion,
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-15')
        );

        $this->assertSame(13, $diasVacaciones);
        $this->assertSame(2, 15 - $diasVacaciones);
    }
}
