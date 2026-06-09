<?php

namespace App\Services\Nomina;

use App\Models\Nomina\NovedadRetroactiva;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NovedadRetroactivaService
{
    private const WITH = [
        'empleado:id,name,email',
        'registrador:id,name,email',
        'supervisor:id,name,email',
        'nomina:id,uuid,periodo_inicio,periodo_fin',
    ];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);

        return NovedadRetroactiva::with(self::WITH)
            ->when(! empty($filters['user_id']), fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['tipo']), fn ($query) => $query->where('tipo', $filters['tipo']))
            ->when(! empty($filters['periodo_inicio']), function ($query) use ($filters) {
                $query->where(function ($subquery) use ($filters) {
                    $subquery->whereDate('aplicar_hasta', '>=', $filters['periodo_inicio'])
                        ->orWhereNull('aplicar_hasta');
                });
            })
            ->when(! empty($filters['periodo_fin']), fn ($query) => $query->whereDate('aplicar_desde', '<=', $filters['periodo_fin']))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('concepto', 'like', "%{$search}%")
                        ->orWhereHas('empleado', fn ($empleado) => $empleado
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('aplicar_desde')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): NovedadRetroactiva
    {
        return NovedadRetroactiva::with(self::WITH)->where('uuid', $uuid)->firstOrFail();
    }

    public function store(array $data): NovedadRetroactiva
    {
        return DB::transaction(function () use ($data) {
            $novedad = NovedadRetroactiva::create([
                ...$data,
                'status' => 'pendiente',
                'registrado_por' => Auth::id(),
            ]);

            Log::info('Novedad retroactiva registrada', [
                'uuid' => $novedad->uuid,
                'user_id' => $novedad->user_id,
                'tipo' => $novedad->tipo,
                'valor' => $novedad->valor,
            ]);

            return $novedad->load(self::WITH);
        });
    }

    public function aprobar(string $uuid, ?string $observacion = null): NovedadRetroactiva
    {
        return $this->gestionar($uuid, 'aprobada', $observacion);
    }

    public function rechazar(string $uuid, ?string $observacion = null): NovedadRetroactiva
    {
        return $this->gestionar($uuid, 'rechazada', $observacion);
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $novedad = $this->getByUuid($uuid);

            if ($novedad->status !== 'pendiente') {
                throw new \LogicException('Solo se pueden eliminar novedades retroactivas pendientes.');
            }

            $novedad->delete();

            Log::info('Novedad retroactiva eliminada', ['uuid' => $novedad->uuid]);
        });
    }

    private function gestionar(string $uuid, string $status, ?string $observacion): NovedadRetroactiva
    {
        return DB::transaction(function () use ($uuid, $status, $observacion) {
            $novedad = $this->getByUuid($uuid);

            if ($novedad->status !== 'pendiente') {
                throw new \LogicException("La novedad retroactiva ya fue {$novedad->status}.");
            }

            $novedad->update([
                'status' => $status,
                'autorizado_por' => Auth::id(),
                'fecha_gestion' => now(),
                'observacion_gestion' => $observacion,
            ]);

            Log::info('Novedad retroactiva gestionada', [
                'uuid' => $novedad->uuid,
                'status' => $status,
                'autorizado_por' => Auth::id(),
            ]);

            return $novedad->fresh(self::WITH);
        });
    }
}
