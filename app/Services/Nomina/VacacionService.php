<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Contratacion;
use App\Models\Nomina\Vacacion;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VacacionService
{
    private const WITH = ['empleado:id,name,email', 'supervisor:id,name,email'];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Vacacion::with(self::WITH)
            ->when(!empty($filters['user_id']),     fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['status']),      fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['tipo']),        fn($q) => $q->where('tipo', $filters['tipo']))
            ->when(!empty($filters['fecha_desde']), fn($q) => $q->whereDate('fecha_inicio', '>=', $filters['fecha_desde']))
            ->when(!empty($filters['fecha_hasta']), fn($q) => $q->whereDate('fecha_inicio', '<=', $filters['fecha_hasta']))
            ->orderByDesc('fecha_inicio')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): Vacacion
    {
        $vacacion = $this->findByUuid($uuid);
        $vacacion->dias_disponibles = $this->getDiasDisponibles($vacacion->user_id);

        return $vacacion;
    }

    private function findByUuid(string $uuid): Vacacion
    {
        return Vacacion::with(self::WITH)->where('uuid', $uuid)->firstOrFail();
    }

    public function getDiasDisponibles(int $userId): int
    {
        $contrato = Contratacion::where('users_id', $userId)
            ->where('status', 1)
            ->latest('inicio_contratacion')
            ->first();

        if (!$contrato) {
            return 0;
        }

        // Ley colombiana: 15 días hábiles por año trabajado
        $diasGanados = Carbon::parse($contrato->inicio_contratacion)->diffInDays(now()) / 365 * 15;

        $diasUsados = Vacacion::where('user_id', $userId)
            ->whereIn('status', ['aprobada'])
            ->sum('dias_habiles');

        return max(0, (int) floor($diasGanados) - (int) $diasUsados);
    }

    public function store(array $data): Vacacion
    {
        return DB::transaction(function () use ($data) {
            $disponibles = $this->getDiasDisponibles($data['user_id']);

            if ($data['dias_habiles'] > $disponibles) {
                throw new \LogicException(
                    "El empleado solo tiene {$disponibles} días de vacaciones disponibles."
                );
            }

            $vacacion = Vacacion::create(array_merge($data, ['status' => 'pendiente']));

            Log::info('Vacación solicitada', [
                'uuid'         => $vacacion->uuid,
                'user_id'      => $vacacion->user_id,
                'fecha_inicio' => $vacacion->fecha_inicio,
                'fecha_fin'    => $vacacion->fecha_fin,
                'dias_habiles' => $vacacion->dias_habiles,
                'tipo'         => $vacacion->tipo,
            ]);

            return $vacacion->load(self::WITH);
        });
    }

    public function aprobar(string $uuid, ?string $observacion = null): Vacacion
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $vacacion = $this->findByUuid($uuid);

            if ($vacacion->status !== 'pendiente') {
                throw new \LogicException("La vacación ya fue {$vacacion->status}.");
            }

            $vacacion->update([
                'status'              => 'aprobada',
                'autorizado_por'      => Auth::id(),
                'fecha_gestion'       => now(),
                'observacion_gestion' => $observacion,
            ]);

            Log::info('Vacación aprobada', [
                'uuid'          => $vacacion->uuid,
                'user_id'       => $vacacion->user_id,
                'autorizado_por' => Auth::id(),
            ]);

            return $vacacion->fresh(self::WITH);
        });
    }

    public function rechazar(string $uuid, ?string $observacion = null): Vacacion
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $vacacion = $this->findByUuid($uuid);

            if ($vacacion->status !== 'pendiente') {
                throw new \LogicException("La vacación ya fue {$vacacion->status}.");
            }

            $vacacion->update([
                'status'              => 'rechazada',
                'autorizado_por'      => Auth::id(),
                'fecha_gestion'       => now(),
                'observacion_gestion' => $observacion,
            ]);

            Log::info('Vacación rechazada', [
                'uuid'    => $vacacion->uuid,
                'user_id' => $vacacion->user_id,
            ]);

            return $vacacion->fresh(self::WITH);
        });
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $vacacion = Vacacion::where('uuid', $uuid)->firstOrFail();

            if ($vacacion->status === 'aprobada') {
                throw new \LogicException('No se puede eliminar una vacación ya aprobada.');
            }

            $vacacion->delete();

            Log::info('Vacación eliminada', ['uuid' => $vacacion->uuid]);
        });
    }
}
