<?php

namespace App\Services\Nomina;

use App\Models\Nomina\HorarioOperacionDiaria;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HorarioOperacionDiariaService
{
    public function porFecha(?string $fecha = null): ?HorarioOperacionDiaria
    {
        $dia = Carbon::parse($fecha ?? now())->toDateString();

        return HorarioOperacionDiaria::with('jornadaLaboral')
            ->whereDate('fecha', $dia)
            ->where('status', true)
            ->first();
    }

    public function guardar(array $data): HorarioOperacionDiaria
    {
        return DB::transaction(function () use ($data) {
            $fecha = Carbon::parse($data['fecha'])->toDateString();

            $horario = HorarioOperacionDiaria::withTrashed()
                ->whereDate('fecha', $fecha)
                ->first();

            if ($horario?->trashed()) {
                $horario->restore();
            }

            $payload = [
                'fecha' => $fecha,
                'jornada_laboral_id' => $data['jornada_laboral_id'] ?? null,
                'hora_salida_pausa' => $data['hora_salida_pausa'] ?? null,
                'hora_ingreso_pausa' => $data['hora_ingreso_pausa'] ?? null,
                'hora_salida_almuerzo' => $data['hora_salida_almuerzo'] ?? null,
                'hora_ingreso_almuerzo' => $data['hora_ingreso_almuerzo'] ?? null,
                'duracion_pausa_minutos' => $data['duracion_pausa_minutos'] ?? null,
                'duracion_almuerzo_minutos' => $data['duracion_almuerzo_minutos'] ?? null,
                'motivo' => $data['motivo'] ?? null,
                'status' => $data['status'] ?? true,
            ];

            if ($horario) {
                $horario->update($payload);
                return $horario->fresh('jornadaLaboral');
            }

            return HorarioOperacionDiaria::create($payload)->load('jornadaLaboral');
        });
    }
}
