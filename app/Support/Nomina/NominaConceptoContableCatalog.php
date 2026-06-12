<?php

namespace App\Support\Nomina;

class NominaConceptoContableCatalog
{
    public static function defaults(): array
    {
        return [
            ['codigo' => 'salario_base', 'nombre' => 'Salario base', 'tipo' => 'devengo', 'puck_numero' => '510506', 'naturaleza' => 'debito'],
            ['codigo' => 'auxilio_transporte', 'nombre' => 'Auxilio de transporte', 'tipo' => 'devengo', 'puck_numero' => '510527', 'naturaleza' => 'debito'],
            ['codigo' => 'horas_extras_diurnas', 'nombre' => 'Horas extras diurnas', 'tipo' => 'devengo', 'puck_numero' => '510515', 'naturaleza' => 'debito'],
            ['codigo' => 'horas_extras_nocturnas', 'nombre' => 'Horas extras nocturnas', 'tipo' => 'devengo', 'puck_numero' => '510515', 'naturaleza' => 'debito'],
            ['codigo' => 'horas_festivas', 'nombre' => 'Horas festivas', 'tipo' => 'devengo', 'puck_numero' => '510515', 'naturaleza' => 'debito'],
            ['codigo' => 'horas_nocturnas_festivas', 'nombre' => 'Horas nocturnas festivas', 'tipo' => 'devengo', 'puck_numero' => '510515', 'naturaleza' => 'debito'],
            ['codigo' => 'comisiones', 'nombre' => 'Comisiones', 'tipo' => 'devengo', 'puck_numero' => '510518', 'naturaleza' => 'debito'],
            ['codigo' => 'novedades_retroactivas_devengo', 'nombre' => 'Novedades retroactivas devengo', 'tipo' => 'devengo', 'puck_numero' => '510595', 'naturaleza' => 'debito'],
            ['codigo' => 'salud_empleado', 'nombre' => 'Salud empleado', 'tipo' => 'deduccion', 'puck_numero' => '237005', 'naturaleza' => 'credito'],
            ['codigo' => 'pension_empleado', 'nombre' => 'Pensión empleado', 'tipo' => 'deduccion', 'puck_numero' => '238030', 'naturaleza' => 'credito'],
            ['codigo' => 'descuentos_adicionales', 'nombre' => 'Descuentos adicionales', 'tipo' => 'deduccion', 'puck_numero' => '138095', 'naturaleza' => 'credito'],
            ['codigo' => 'novedades_retroactivas_deduccion', 'nombre' => 'Novedades retroactivas deducción', 'tipo' => 'deduccion', 'puck_numero' => '237095', 'naturaleza' => 'credito'],
            ['codigo' => 'neto_pagar', 'nombre' => 'Neto a pagar', 'tipo' => 'neto', 'puck_numero' => '250505', 'naturaleza' => 'credito'],
        ];
    }

    public static function byCodigo(): array
    {
        return collect(self::defaults())->keyBy('codigo')->all();
    }

    public static function puckNumero(string $codigo): ?string
    {
        return self::byCodigo()[$codigo]['puck_numero'] ?? null;
    }
}
