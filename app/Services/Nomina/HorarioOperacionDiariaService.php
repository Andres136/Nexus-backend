<?php

namespace App\Services\Nomina;

use App\Models\Nomina\HorarioOperacionDiaria;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HorarioOperacionDiariaService
{
    public function porFecha(?string $fecha = null, ?int $kioskoDeviceId = null): ?HorarioOperacionDiaria
    {
        $dia = Carbon::parse($fecha ?? now())->toDateString();

        $query = HorarioOperacionDiaria::with(['jornadaLaboral', 'kiosko.sede', 'kiosko.bodega'])
            ->whereDate('fecha', $dia)
            ->where('status', true);

        if ($kioskoDeviceId) {
            $query->where(function ($q) use ($kioskoDeviceId) {
                $q->where('kiosko_device_id', $kioskoDeviceId)
                    ->orWhereNull('kiosko_device_id');
            })->orderByRaw('CASE WHEN kiosko_device_id = ? THEN 0 ELSE 1 END', [$kioskoDeviceId]);
        } else {
            $query->whereNull('kiosko_device_id');
        }

        return $query->first();
    }

    public function guardar(array $data): HorarioOperacionDiaria
    {
        return DB::transaction(function () use ($data) {
            $fecha = Carbon::parse($data['fecha'])->toDateString();
            $kioskoDeviceId = $data['kiosko_device_id'] ?? null;

            $horario = HorarioOperacionDiaria::withTrashed()
                ->whereDate('fecha', $fecha)
                ->when($kioskoDeviceId, fn ($q) => $q->where('kiosko_device_id', $kioskoDeviceId), fn ($q) => $q->whereNull('kiosko_device_id'))
                ->first();

            if ($horario?->trashed()) {
                $horario->restore();
            }

            $payload = [
                'fecha' => $fecha,
                'kiosko_device_id' => $kioskoDeviceId,
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
                return $horario->fresh(['jornadaLaboral', 'kiosko.sede', 'kiosko.bodega']);
            }

            return HorarioOperacionDiaria::create($payload)->load(['jornadaLaboral', 'kiosko.sede', 'kiosko.bodega']);
        });
    }
}
