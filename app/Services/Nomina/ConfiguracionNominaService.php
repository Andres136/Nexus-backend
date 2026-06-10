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
