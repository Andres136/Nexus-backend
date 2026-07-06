<?php

namespace App\Services\Nomina;

use App\Models\Nomina\HorarioUsuarioSemanal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class HorarioUsuarioSemanalService
{
    private const WITH = ['empleado:id,name,email', 'jornadaLaboral'];

    public function porUsuario(int $userId): Collection
    {
        return HorarioUsuarioSemanal::with(self::WITH)
            ->where('user_id', $userId)
            ->orderBy('dia_semana')
            ->get();
    }

    public function porUsuarioYFecha(int $userId, ?string $fecha = null): ?HorarioUsuarioSemanal
    {
        $diaSemana = Carbon::parse($fecha ?? now(config('app.timezone')))->dayOfWeekIso;

        return HorarioUsuarioSemanal::with(self::WITH)
            ->where('user_id', $userId)
            ->where('dia_semana', $diaSemana)
            ->where('status', true)
            ->first();
    }

    public function guardarSemana(array $data): Collection|\Illuminate\Support\Collection
    {
        return DB::transaction(function () use ($data) {
            $aplicarTodos = (bool) ($data['aplicar_todos'] ?? false);
            $userIds = $aplicarTodos
                ? User::whereHas('contratacionActivaNomina')->pluck('id')
                : collect([(int) $data['user_id']]);

            foreach ($userIds as $userId) {
                foreach ($data['horarios'] as $item) {
                    $horario = HorarioUsuarioSemanal::withTrashed()
                        ->where('user_id', $userId)
                        ->where('dia_semana', $item['dia_semana'])
                        ->first();

                    if ($horario?->trashed()) {
                        $horario->restore();
                    }

                    $payload = [
                        'user_id' => $userId,
                        'dia_semana' => $item['dia_semana'],
                        'jornada_laboral_id' => $item['jornada_laboral_id'] ?? null,
                        'hora_entrada' => $item['hora_entrada'] ?? null,
                        'hora_entrada_limite' => $item['hora_entrada_limite'] ?? null,
                        'hora_salida_pausa' => $item['hora_salida_pausa'] ?? null,
                        'hora_ingreso_pausa' => $item['hora_ingreso_pausa'] ?? null,
                        'hora_salida_almuerzo' => $item['hora_salida_almuerzo'] ?? null,
                        'hora_ingreso_almuerzo' => $item['hora_ingreso_almuerzo'] ?? null,
                        'hora_salida' => $item['hora_salida'] ?? null,
                        'duracion_pausa_minutos' => $item['duracion_pausa_minutos'] ?? null,
                        'duracion_almuerzo_minutos' => $item['duracion_almuerzo_minutos'] ?? null,
                        'status' => $item['status'] ?? true,
                    ];

                    $horario ? $horario->update($payload) : HorarioUsuarioSemanal::create($payload);
                }
            }

            if ($aplicarTodos) {
                return collect([
                    'usuarios_actualizados' => $userIds->count(),
                    'dias_actualizados' => count($data['horarios']),
                ]);
            }

            return $this->porUsuario((int) $data['user_id']);
        });
    }
}
