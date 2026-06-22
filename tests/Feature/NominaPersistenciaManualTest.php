<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NominaPersistenciaManualTest extends TestCase
{
    public function test_no_existen_rutas_para_crear_o_actualizar_nomina_directamente(): void
    {
        $this->assertRouteMethodMissing('api/nomina/nominas', 'POST');
        $this->assertRouteMethodMissing('api/nomina/nominas/{nomina}', 'PUT');
        $this->assertRouteMethodMissing('api/nomina/nominas/{nomina}', 'PATCH');
    }

    public function test_no_existen_rutas_de_liquidacion_directa_individual_o_masiva(): void
    {
        $this->assertRouteMethodMissing('api/nomina/nominas/liquidar', 'POST');
        $this->assertRouteMethodMissing('api/nomina/nominas/liquidar-masivo', 'POST');
    }

    public function test_se_conserva_liquidacion_desde_preliquidacion_aprobada(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route) => $route->uri() === 'api/nomina/nominas/preliquidaciones/{uuid}/liquidar'
                && in_array('POST', $route->methods(), true));

        $this->assertNotNull($route);
    }

    public function test_no_existe_borrado_de_nomina_y_si_existe_reversion_formal(): void
    {
        $this->assertRouteMethodMissing('api/nomina/nominas/{nomina}', 'DELETE');

        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route) => $route->uri() === 'api/nomina/nominas/{uuid}/revertir'
                && in_array('POST', $route->methods(), true));

        $this->assertNotNull($route);
    }

    private function assertRouteMethodMissing(string $uri, string $method): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route) => $route->uri() === $uri
                && in_array($method, $route->methods(), true));

        $this->assertNull($route, "La ruta {$method} {$uri} no debe existir.");
    }
}
