<?php

namespace App\Services\Nomina;

use App\Models\Nomina\HoraExtra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HoraExtraService
{
    private const WITH = ['empleado:id,name,email', 'supervisor:id,name,email'];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return HoraExtra::with(self::WITH)
            ->when(!empty($filters['user_id']),  fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['status']),   fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['tipo']),     fn($q) => $q->where('tipo', $filters['tipo']))
            ->when(!empty($filters['fecha_desde']), fn($q) => $q->whereDate('fecha', '>=', $filters['fecha_desde']))
            ->when(!empty($filters['fecha_hasta']), fn($q) => $q->whereDate('fecha', '<=', $filters['fecha_hasta']))
            ->orderByDesc('fecha')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): HoraExtra
    {
        return HoraExtra::with(self::WITH)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * Crea un registro de horas extras para cada usuario en $data['users'].
     * Retorna la colección de registros creados.
     */
    public function store(array $data): Collection
    {
        return DB::transaction(function () use ($data) {
            $registros = collect();

            foreach ($data['users'] as $userId) {
                $horaExtra = HoraExtra::create([
                    'user_id' => $userId,
                    'fecha'   => $data['fecha'],
                    'horas'   => $data['horas'],
                    'tipo'    => $data['tipo'],
                    'motivo'  => $data['motivo'] ?? null,
                    'status'  => 'pendiente',
                ]);

                $registros->push($horaExtra->load(self::WITH));
            }

            Log::info('Horas extras registradas', [
                'users'  => $data['users'],
                'fecha'  => $data['fecha'],
                'horas'  => $data['horas'],
                'tipo'   => $data['tipo'],
                'total'  => $registros->count(),
            ]);

            return $registros;
        });
    }

    public function aprobar(string $uuid, ?string $observacion = null): HoraExtra
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $horaExtra = $this->getByUuid($uuid);

            if ($horaExtra->status !== 'pendiente') {
                throw new \LogicException("La hora extra ya fue {$horaExtra->status}.");
            }

            $horaExtra->update([
                'status'              => 'aprobada',
                'autorizado_por'      => Auth::id(),
                'fecha_gestion'       => now(),
                'observacion_gestion' => $observacion,
            ]);

            Log::info('Hora extra aprobada', [
                'uuid'          => $horaExtra->uuid,
                'user_id'       => $horaExtra->user_id,
                'autorizado_por'=> Auth::id(),
            ]);

            return $horaExtra->fresh(self::WITH);
        });
    }

    public function rechazar(string $uuid, ?string $observacion = null): HoraExtra
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $horaExtra = $this->getByUuid($uuid);

            if ($horaExtra->status !== 'pendiente') {
                throw new \LogicException("La hora extra ya fue {$horaExtra->status}.");
            }

            $horaExtra->update([
                'status'              => 'rechazada',
                'autorizado_por'      => Auth::id(),
                'fecha_gestion'       => now(),
                'observacion_gestion' => $observacion,
            ]);

            Log::info('Hora extra rechazada', [
                'uuid'    => $horaExtra->uuid,
                'user_id' => $horaExtra->user_id,
            ]);

            return $horaExtra->fresh(self::WITH);
        });
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $horaExtra = $this->getByUuid($uuid);

            if ($horaExtra->status === 'aprobada') {
                throw new \LogicException('No se puede eliminar una hora extra ya aprobada.');
            }

            $horaExtra->delete();

            Log::info('Hora extra eliminada', ['uuid' => $horaExtra->uuid]);
        });
    }
}
