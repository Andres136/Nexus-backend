<?php

namespace Tests\Unit;

use App\Services\Nomina\AjusteSalarialContratacionService;
use App\Services\Nomina\ConfiguracionNominaService;
use App\Services\Nomina\NominaService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class NominaReversionTest extends TestCase
{
    public function test_invierte_la_naturaleza_de_los_asientos_contables(): void
    {
        $service = new NominaService(
            $this->createMock(AjusteSalarialContratacionService::class),
            $this->createMock(ConfiguracionNominaService::class),
        );
        $method = new ReflectionMethod(NominaService::class, 'invertirAsientos');
        $method->setAccessible(true);

        $resultado = $method->invoke($service, [
            ['codigo' => 'salario', 'naturaleza' => 'debito', 'valor' => 100],
            ['codigo' => 'neto', 'naturaleza' => 'credito', 'valor' => 100],
        ]);

        $this->assertSame('credito', $resultado[0]['naturaleza']);
        $this->assertSame('debito', $resultado[1]['naturaleza']);
        $this->assertSame('debito', $resultado[0]['naturaleza_original']);
        $this->assertSame('credito', $resultado[1]['naturaleza_original']);
    }
}
