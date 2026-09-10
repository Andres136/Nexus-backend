<?php

namespace Tests\Unit;

use App\Services\Crm\GestionCarteraService;
use PHPUnit\Framework\TestCase;

class GestionCarteraServiceTest extends TestCase
{
    public function test_envia_correo_al_cliente_cuando_tiene_cartera_vencida(): void
    {
        $service = new GestionCarteraService();

        $this->assertTrue($service->debeEnviarCorreoAlCliente([
            'tiene_vencida' => true,
            'tiene_proxima' => false,
        ]));
    }

    public function test_no_envia_correo_al_cliente_si_solo_tiene_cartera_proxima_a_vencer(): void
    {
        $service = new GestionCarteraService();

        $this->assertFalse($service->debeEnviarCorreoAlCliente([
            'tiene_vencida' => false,
            'tiene_proxima' => true,
        ]));
    }

    public function test_no_envia_correo_al_cliente_si_no_tiene_cartera_pendiente(): void
    {
        $service = new GestionCarteraService();

        $this->assertFalse($service->debeEnviarCorreoAlCliente(null));
    }
}
