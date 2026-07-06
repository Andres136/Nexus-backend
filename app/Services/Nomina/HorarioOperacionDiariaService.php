<?php

namespace App\Services\Nomina;

use App\Models\Nomina\HorarioOperacionDiaria;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HorarioOperacionDiariaService
{
    public function porFecha(?string $fecha = null, ?int $kioskoDeviceId = null, ?int $userId = null): ?HorarioOperacionDiaria
    {
        $dia = Carbon::parse($fecha ?? now())->toDateString();

        $query = HorarioOperacionDiaria::with(['jornadaLaboral', 'kiosko.sede', 'kiosko.bodega', 'usuario:id,name,email,sede_id'])
            ->whereDate('fecha', $dia)
            ->where('status', true);

        if ($kioskoDeviceId) {
            $query->where(function ($q) use ($kioskoDeviceId) {
                $q->where('kiosko_device_id', $kioskoDeviceId)
                    ->orWhereNull('kiosko_device_id');
            });
        } else {
            $query->whereNull('kiosko_device_id');
        }

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereNull('user_id');
            });
        } else {
            $query->whereNull('user_id');
        }

        if ($kioskoDeviceId && $userId) {
            $query->orderByRaw(
                'CASE WHEN kiosko_device_id = ? AND user_id = ? THEN 0 WHEN kiosko_device_id IS NULL AND user_id = ? THEN 1 WHEN kiosko_device_id = ? AND user_id IS NULL THEN 2 ELSE 3 END',
                [$kioskoDeviceId, $userId, $userId, $kioskoDeviceId]
            );
        } elseif ($kioskoDeviceId) {
            $query->orderByRaw('CASE WHEN kiosko_device_id = ? THEN 0 ELSE 1 END', [$kioskoDeviceId]);
        } elseif ($userId) {
            $query->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$userId]);
        }

        return $query->first();
    }

    public function guardar(array $data): HorarioOperacionDiaria|\Illuminate\Support\Collection
    {
        return DB::transaction(function () use ($data) {
            $fecha = Carbon::parse($data['fecha'])->toDateString();
            $kioskoDeviceId = $data['kiosko_device_id'] ?? null;
            $users = collect($data['users'] ?? [])
                ->filter()
                ->map(fn ($userId) => (int) $userId)
                ->unique()
                ->values();

            if ($users->isNotEmpty()) {
                return $users->map(fn (int $userId) => $this->guardarUno($data, $fecha, $kioskoDeviceId, $userId));
            }

            return $this->guardarUno($data, $fecha, $kioskoDeviceId, $data['user_id'] ?? null);
        });
    }

    private function guardarUno(array $data, string $fecha, ?int $kioskoDeviceId, ?int $userId = null): HorarioOperacionDiaria
    {
        $horario = HorarioOperacionDiaria::withTrashed()
            ->whereDate('fecha', $fecha)
            ->when($kioskoDeviceId, fn ($q) => $q->where('kiosko_device_id', $kioskoDeviceId), fn ($q) => $q->whereNull('kiosko_device_id'))
            ->when($userId, fn ($q) => $q->where('user_id', $userId), fn ($q) => $q->whereNull('user_id'))
            ->first();

        if ($horario?->trashed()) {
            $horario->restore();
        }

        $payload = [
            'fecha' => $fecha,
            'kiosko_device_id' => $kioskoDeviceId,
            'user_id' => $userId,
            'jornada_laboral_id' => $data['jornada_laboral_id'] ?? null,
            'hora_entrada' => $data['hora_entrada'] ?? null,
            'hora_entrada_limite' => $data['hora_entrada_limite'] ?? null,
            'hora_salida_pausa' => $data['hora_salida_pausa'] ?? null,
            'hora_ingreso_pausa' => $data['hora_ingreso_pausa'] ?? null,
            'hora_salida_almuerzo' => $data['hora_salida_almuerzo'] ?? null,
            'hora_ingreso_almuerzo' => $data['hora_ingreso_almuerzo'] ?? null,
            'hora_salida' => $data['hora_salida'] ?? null,
            'duracion_pausa_minutos' => $data['duracion_pausa_minutos'] ?? null,
            'duracion_almuerzo_minutos' => $data['duracion_almuerzo_minutos'] ?? null,
            'motivo' => $data['motivo'] ?? null,
            'status' => $data['status'] ?? true,
        ];

        if ($horario) {
            $horario->update($payload);

            return $horario->fresh(['jornadaLaboral', 'kiosko.sede', 'kiosko.bodega', 'usuario:id,name,email,sede_id']);
        }

        return HorarioOperacionDiaria::create($payload)->load(['jornadaLaboral', 'kiosko.sede', 'kiosko.bodega', 'usuario:id,name,email,sede_id']);
    }
}
