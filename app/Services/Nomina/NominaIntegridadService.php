<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Descuento;
use App\Models\Nomina\Nomina;
use LogicException;

class NominaIntegridadService
{
    public const ESTADOS_INMUTABLES = [
        'aprobado',
        'exportado',
        'cerrado',
    ];

    public function asegurarNominaEditable(Nomina $nomina): void
    {
        if (in_array($nomina->estado_contable, self::ESTADOS_INMUTABLES, true)) {
            throw new LogicException(
                "La nómina no se puede modificar porque su estado contable es {$nomina->estado_contable}."
            );
        }
    }

    public function asegurarRegistroNoAplicado(?Nomina $nomina, string $registro): void
    {
        if ($nomina) {
            throw new LogicException(
                "No se puede modificar {$registro} porque ya fue aplicado a la nómina {$nomina->uuid}."
            );
        }
    }

    public function asegurarDescuentoNoAplicado(Descuento $descuento): void
    {
        $nomina = Nomina::query()
            ->select(['id', 'uuid', 'descuento_id'])
            ->where('descuento_id', $descuento->id)
            ->first();

        $this->asegurarRegistroNoAplicado($nomina, 'el descuento');
    }
}
