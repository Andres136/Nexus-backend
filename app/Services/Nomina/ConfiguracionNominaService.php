<?php

namespace App\Services\Nomina;

use App\Models\Nomina\ConfiguracionNomina;
use Illuminate\Support\Facades\DB;

class ConfiguracionNominaService
{
    public function actual(): ConfiguracionNomina
    {
        return ConfiguracionNomina::where('status', true)
            ->latest()
            ->first()
            ?? ConfiguracionNomina::create([
                'nombre' => 'Configuración general',
                'porcentaje_salud_empleado' => 4,
                'porcentaje_pension_empleado' => 4,
                'status' => true,
            ]);
    }

    public function guardar(array $data): ConfiguracionNomina
    {
        return DB::transaction(function () use ($data) {
            $config = $this->actual();

            $config->update([
                'nombre' => $data['nombre'] ?? $config->nombre,
                'porcentaje_salud_empleado' => $data['porcentaje_salud_empleado'],
                'porcentaje_pension_empleado' => $data['porcentaje_pension_empleado'],
                'status' => $data['status'] ?? true,
            ]);

            return $config->fresh();
        });
    }
}
