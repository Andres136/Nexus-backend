<?php

namespace App\Services\Nomina;

use App\Models\Nomina\RecuperacionTiempo;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecuperacionTiempoService
{
    private const WITH = [
        'empleado:id,name,email',
        'autorizador:id,name,email',
        'sesion:id,uuid,registro_diario,hora_entrada,hora_salida,minutos_trabajados',
    ];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return RecuperacionTiempo::with(self::WITH)
            ->when(! empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['fecha_inicio']), fn ($q) => $q->whereDate('fecha', '>=', $filters['fecha_inicio']))
            ->when(! empty($filters['fecha_fin']), fn ($q) => $q->whereDate('fecha', '<=', $filters['fecha_fin']))
            ->orderByDesc('fecha')
            ->paginate($perPage);
    }

    public function store(array $data): RecuperacionTiempo
    {
        return DB::transaction(function () use ($data) {
            $inicio = Carbon::parse($data['fecha'].' '.$data['hora_inicio']);
            $fin = Carbon::parse($data['fecha'].' '.$data['hora_fin']);
            $minutos = (int) $inicio->diffInMinutes($fin);

            if ($minutos <= 0) {
                throw ValidationException::withMessages([
                    'hora_fin' => 'La hora final debe ser posterior a la hora inicial.',
                ]);
            }

            $registro = RecuperacionTiempo::create([
                'user_id' => (int) $data['user_id'],
                'fecha' => $data['fecha'],
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'minutos_autorizados' => $minutos,
                'minutos_usados' => 0,
                'motivo' => $data['motivo'],
                'status' => 'aprobada',
                'autorizado_por' => Auth::id(),
                'fecha_gestion' => now(),
                'observacion_gestion' => $data['observacion_gestion'] ?? null,
            ]);

            return $registro->load(self::WITH);
        });
    }

    public function anular(string $uuid): RecuperacionTiempo
    {
        return DB::transaction(function () use ($uuid) {
            $registro = RecuperacionTiempo::where('uuid', $uuid)->firstOrFail();

            if ($registro->minutos_usados > 0) {
                throw new \LogicException('No se puede anular una recuperación que ya fue usada por el kiosko.');
            }

            $registro->update([
                'status' => 'anulada',
                'autorizado_por' => Auth::id(),
                'fecha_gestion' => now(),
            ]);

            return $registro->fresh(self::WITH);
        });
    }
}
