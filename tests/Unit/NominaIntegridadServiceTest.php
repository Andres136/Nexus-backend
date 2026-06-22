<?php

namespace Tests\Unit;

use App\Models\Nomina\Nomina;
use App\Services\Nomina\NominaIntegridadService;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NominaIntegridadServiceTest extends TestCase
{
    #[DataProvider('estadosInmutables')]
    public function test_bloquea_nominas_con_estado_contable_inmutable(string $estado): void
    {
        $nomina = new Nomina();
        $nomina->setRawAttributes([
            'uuid' => 'nomina-prueba',
            'estado_contable' => $estado,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("estado contable es {$estado}");

        (new NominaIntegridadService())->asegurarNominaEditable($nomina);
    }

    public function test_permite_editar_nomina_pendiente(): void
    {
        $nomina = new Nomina();
        $nomina->setRawAttributes([
            'uuid' => 'nomina-prueba',
            'estado_contable' => 'pendiente',
        ]);

        (new NominaIntegridadService())->asegurarNominaEditable($nomina);

        $this->addToAssertionCount(1);
    }

    public function test_bloquea_un_registro_ya_aplicado_a_nomina(): void
    {
        $nomina = new Nomina();
        $nomina->setRawAttributes([
            'uuid' => 'nomina-aplicada',
            'estado_contable' => 'pendiente',
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('ya fue aplicado a la nómina nomina-aplicada');

        (new NominaIntegridadService())->asegurarRegistroNoAplicado($nomina, 'la comisión');
    }

    public static function estadosInmutables(): array
    {
        return [
            'aprobada' => ['aprobado'],
            'exportada' => ['exportado'],
            'cerrada' => ['cerrado'],
        ];
    }
}
