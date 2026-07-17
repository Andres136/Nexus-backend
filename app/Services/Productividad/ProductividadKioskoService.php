<?php

namespace App\Services\Productividad;

use App\Services\Vsm\AlistamientoService;

class ProductividadKioskoService
{
    public function __construct(
        private readonly MiDiaService $miDiaService,
        private readonly AlistamientoService $alistamientoService,
    ) {}

    public function procesarMarcacion(int $userId, array $data): array
    {
        $motivo = match (true) {
            ! empty($data['hora_salida']) => 'Pausa automática por salida de jornada en kiosko',
            ! empty($data['hora_salida_almuerzo']) => 'Pausa automática por salida a almuerzo en kiosko',
            ! empty($data['hora_salida_brake']) => 'Pausa automática por salida a descanso en kiosko',
            default => null,
        };

        if (! $motivo) {
            return ['actividad_pausada' => false, 'alistamientos_pausados' => 0];
        }

        return [
            'actividad_pausada' => $this->miDiaService->pausarPorKiosko($userId, $motivo) !== null,
            'alistamientos_pausados' => $this->alistamientoService->pausarAlistamientosActivosDelUsuario($userId, $motivo),
        ];
    }
}
