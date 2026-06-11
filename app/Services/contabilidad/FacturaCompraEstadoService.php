<?php

namespace App\Services\contabilidad;

use App\EstadoEnum;

class FacturaCompraEstadoService
{
    public function calcular(float $total, float $totalPagado): array
    {
        $saldoPendiente = max($total - $totalPagado, 0);

        if ($total > 0 && $totalPagado >= $total) {
            $estado = EstadoEnum::PAGADA;
        } elseif ($totalPagado > 0) {
            $estado = EstadoEnum::PAGO_PARCIAL;
        } else {
            $estado = EstadoEnum::PENDIENTE;
        }

        return [
            'estado_id' => $estado->value,
            'saldo_pendiente' => $saldoPendiente,
        ];
    }
}
