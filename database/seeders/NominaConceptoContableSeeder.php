<?php

namespace Database\Seeders;

use App\Models\contabilidad\Puck;
use App\Models\Nomina\NominaConceptoContable;
use App\Support\Nomina\NominaConceptoContableCatalog;
use Illuminate\Database\Seeder;

class NominaConceptoContableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (NominaConceptoContableCatalog::defaults() as $concepto) {
            $puck = Puck::where('numero', $concepto['puck_numero'])->first();

            NominaConceptoContable::updateOrCreate(
                ['codigo' => $concepto['codigo']],
                [
                    'nombre' => $concepto['nombre'],
                    'tipo' => $concepto['tipo'],
                    'puck_id' => $puck?->id,
                    'naturaleza' => $concepto['naturaleza'],
                    'requiere_tercero' => $concepto['requiere_tercero'] ?? true,
                    'requiere_centro_costo' => $concepto['requiere_centro_costo'] ?? false,
                    'activo' => true,
                ]
            );
        }
    }
}
