<?php

namespace App\Services\Tic;

use App\Models\Tic\Asignaciones;
use App\Models\Tic\SalidaTemporal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalidaTemporalService
{
    public function __construct(private readonly SalidaTemporalActaService $actaService) {}

    public function registrarSalida(int $asignacionId, array $data, User $creadaPor): SalidaTemporal
    {
        return DB::transaction(function () use ($asignacionId, $data, $creadaPor) {
            $asignacion = Asignaciones::with('usuarioRecibe')->lockForUpdate()->findOrFail($asignacionId);

            if (! $asignacion->activo) {
                throw ValidationException::withMessages([
                    'asignacion_id' => 'La asignación no está activa.',
                ]);
            }

            if (! $asignacion->usuarioRecibe) {
                throw ValidationException::withMessages([
                    'asignacion_id' => 'La asignación no tiene un usuario asignado.',
                ]);
            }

            $tieneSalidaAbierta = SalidaTemporal::where('asignacion_id', $asignacion->id)
                ->where('estado', 'salida')
                ->exists();

            if ($tieneSalidaAbierta) {
                throw ValidationException::withMessages([
                    'asignacion_id' => 'Este equipo ya tiene una salida temporal en curso.',
                ]);
            }

            $salida = SalidaTemporal::create([
                'asignacion_id' => $asignacion->id,
                'usuario_id' => $asignacion->usuario_asignacion_id,
                'motivo' => $data['motivo'] ?? null,
                'fecha_salida' => now(),
                'fecha_retorno_estimada' => $data['fecha_retorno_estimada'] ?? null,
                'estado' => 'salida',
                'creada_por' => $creadaPor->id,
            ]);

            $this->actaService->generarAutomatico($salida, 'salida', $creadaPor);

            return $salida->fresh(['asignacion.producto', 'usuario', 'actaSalida']);
        });
    }

    public function registrarRetorno(int $salidaTemporalId, array $data, User $creadaPor): SalidaTemporal
    {
        return DB::transaction(function () use ($salidaTemporalId, $data, $creadaPor) {
            $salida = SalidaTemporal::with('usuario')->lockForUpdate()->findOrFail($salidaTemporalId);

            if ($salida->estado === 'retornado') {
                throw ValidationException::withMessages([
                    'salida_temporal_id' => 'Esta salida temporal ya fue retornada.',
                ]);
            }

            $salida->update([
                'fecha_retorno_real' => now(),
                'observaciones_retorno' => $data['observaciones_retorno'] ?? null,
                'estado' => 'retornado',
            ]);

            $this->actaService->generarAutomatico($salida, 'retorno', $creadaPor);

            return $salida->fresh(['asignacion.producto', 'usuario', 'actaRetorno']);
        });
    }
}
