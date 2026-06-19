<?php

namespace App\Services\Nomina;

use App\Models\Nomina\ConfiguracionNomina;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                'porcentaje_salud_empleador' => 8.5,
                'porcentaje_pension_empleador' => 12,
                'porcentaje_arl' => 2.436,
                'porcentaje_sena' => 2,
                'porcentaje_icbf' => 3,
                'porcentaje_caja_compensacion' => 4,
                'recargo_extra_diurna' => 0.25,
                'recargo_extra_nocturna' => 0.75,
                'recargo_festiva' => 0.75,
                'recargo_nocturna_festiva' => 1.10,
                'porcentaje_incapacidad' => 0.6667,
                'hora_inicio_nocturna' => '19:00',
                'hora_fin_nocturna' => '06:00',
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
                'porcentaje_salud_empleador' => $data['porcentaje_salud_empleador'],
                'porcentaje_pension_empleador' => $data['porcentaje_pension_empleador'],
                'porcentaje_arl' => $data['porcentaje_arl'],
                'porcentaje_sena' => $data['porcentaje_sena'],
                'porcentaje_icbf' => $data['porcentaje_icbf'],
                'porcentaje_caja_compensacion' => $data['porcentaje_caja_compensacion'],
                'recargo_extra_diurna' => $data['recargo_extra_diurna'],
                'recargo_extra_nocturna' => $data['recargo_extra_nocturna'],
                'recargo_festiva' => $data['recargo_festiva'],
                'recargo_nocturna_festiva' => $data['recargo_nocturna_festiva'],
                'porcentaje_incapacidad' => $data['porcentaje_incapacidad'],
                'hora_inicio_nocturna' => $data['hora_inicio_nocturna'],
                'hora_fin_nocturna' => $data['hora_fin_nocturna'],
                'status' => $data['status'] ?? true,
            ]);

            return $config->fresh();
        });
    }

    public function guardarFirma(UploadedFile $firma): ConfiguracionNomina
    {
        return DB::transaction(function () use ($firma) {
            $config = $this->actual();
            $anterior = $config->firma_talento_humano;
            $path = $firma->store('nomina/firmas', 'public');

            $config->update(['firma_talento_humano' => $path]);

            if ($anterior && $anterior !== $path) {
                Storage::disk('public')->delete($anterior);
            }

            return $config->fresh();
        });
    }

    public function firmaTalentoHumanoPath(): ?string
    {
        $path = $this->actual()->firma_talento_humano;
        if (! $path) {
            return null;
        }

        $absolutePath = storage_path("app/public/{$path}");

        return file_exists($absolutePath) ? $absolutePath : null;
    }
}
