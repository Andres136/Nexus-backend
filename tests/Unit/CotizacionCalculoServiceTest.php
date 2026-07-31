<?php

namespace Tests\Unit;

use App\Services\Crm\CotizacionCalculoService;
use PHPUnit\Framework\TestCase;

class CotizacionCalculoServiceTest extends TestCase
{
    public function test_calcula_una_bolsa_con_la_misma_formula_del_formulario(): void
    {
        $resultado = (new CotizacionCalculoService())->calcular([
            'ancho_cm' => 30,
            'largo_cm' => 40,
            'calibre' => 2,
            'precio_total' => 10000,
            'cantidad' => 10,
        ]);

        $this->assertSame(11.0, $resultado['peso_bolsa']);
        $this->assertSame(91, $resultado['numero_bolsas']);
        $this->assertSame(110.0, $resultado['valor_unitario']);
        $this->assertSame(1100.0, $resultado['valor_paquete']);
        $this->assertSame(1309.0, $resultado['valor_total']);
    }

    public function test_admite_precio_unitario_para_productos_sin_medidas(): void
    {
        $resultado = (new CotizacionCalculoService())->calcular([
            'valor_unitario' => 2500,
            'cantidad' => 4,
        ]);

        $this->assertSame(1, $resultado['numero_bolsas']);
        $this->assertSame(10000.0, $resultado['valor_paquete']);
        $this->assertSame(11900.0, $resultado['valor_total']);
    }
}
