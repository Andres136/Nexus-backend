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
            ['codigo' => 'salud_empleador_gasto', 'nombre' => 'Gasto salud empleador', 'tipo' => 'aporte_empleador', 'puck_numero' => '510568', 'naturaleza' => 'debito', 'requiere_tercero' => false],
            ['codigo' => 'salud_empleador_pagar', 'nombre' => 'Salud empleador por pagar', 'tipo' => 'aporte_empleador', 'puck_numero' => '250505', 'naturaleza' => 'credito', 'requiere_tercero' => false],
            ['codigo' => 'pension_empleador_gasto', 'nombre' => 'Gasto pensión empleador', 'tipo' => 'aporte_empleador', 'puck_numero' => '510570', 'naturaleza' => 'debito', 'requiere_tercero' => false],
            ['codigo' => 'pension_empleador_pagar', 'nombre' => 'Pensión empleador por pagar', 'tipo' => 'aporte_empleador', 'puck_numero' => '250510', 'naturaleza' => 'credito', 'requiere_tercero' => false],
            ['codigo' => 'arl_gasto', 'nombre' => 'Gasto ARL', 'tipo' => 'aporte_empleador', 'puck_numero' => '510572', 'naturaleza' => 'debito', 'requiere_tercero' => false],
            ['codigo' => 'arl_pagar', 'nombre' => 'ARL por pagar', 'tipo' => 'aporte_empleador', 'puck_numero' => '250515', 'naturaleza' => 'credito', 'requiere_tercero' => false],
            ['codigo' => 'sena_gasto', 'nombre' => 'Gasto SENA', 'tipo' => 'aporte_empleador', 'puck_numero' => '510578', 'naturaleza' => 'debito', 'requiere_tercero' => false],
            ['codigo' => 'sena_pagar', 'nombre' => 'SENA por pagar', 'tipo' => 'aporte_empleador', 'puck_numero' => '250520', 'naturaleza' => 'credito', 'requiere_tercero' => false],
            ['codigo' => 'icbf_gasto', 'nombre' => 'Gasto ICBF', 'tipo' => 'aporte_empleador', 'puck_numero' => '510575', 'naturaleza' => 'debito', 'requiere_tercero' => false],
            ['codigo' => 'icbf_pagar', 'nombre' => 'ICBF por pagar', 'tipo' => 'aporte_empleador', 'puck_numero' => '250525', 'naturaleza' => 'credito', 'requiere_tercero' => false],
            ['codigo' => 'caja_compensacion_gasto', 'nombre' => 'Gasto caja de compensación', 'tipo' => 'aporte_empleador', 'puck_numero' => '510572', 'naturaleza' => 'debito', 'requiere_tercero' => false],
            ['codigo' => 'caja_compensacion_pagar', 'nombre' => 'Caja de compensación por pagar', 'tipo' => 'aporte_empleador', 'puck_numero' => '250530', 'naturaleza' => 'credito', 'requiere_tercero' => false],
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
