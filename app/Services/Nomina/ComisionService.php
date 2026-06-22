<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Comision;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComisionService
{
    private const WITH = [
        'empleado:id,name,email',
        'registrador:id,name,email',
        'supervisor:id,name,email',
        'nomina:id,uuid,periodo_inicio,periodo_fin,estado_contable',
    ];

    public function __construct(
        private readonly NominaIntegridadService $integridadService,
    ) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Comision::with(self::WITH)
            ->when(! empty($filters['user_id']), fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['periodo_inicio']), fn ($query) => $query->whereDate('periodo_fin', '>=', $filters['periodo_inicio']))
            ->when(! empty($filters['periodo_fin']), fn ($query) => $query->whereDate('periodo_inicio', '<=', $filters['periodo_fin']))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('concepto', 'like', "%{$search}%")
                        ->orWhereHas('empleado', fn ($empleado) => $empleado
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('periodo_fin')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): Comision
    {
        return Comision::with(self::WITH)->where('uuid', $uuid)->firstOrFail();
    }

    public function store(array $data): Comision
    {
        return DB::transaction(function () use ($data) {
            $comision = Comision::create([
                ...$data,
                'status' => 'pendiente',
                'registrado_por' => Auth::id(),
            ]);

            Log::info('Comisión registrada', [
                'uuid' => $comision->uuid,
                'user_id' => $comision->user_id,
                'valor' => $comision->valor,
            ]);

            return $comision->load(self::WITH);
        });
    }

    public function update(string $uuid, array $data): Comision
    {
        return DB::transaction(function () use ($uuid, $data) {
            $comision = $this->getByUuid($uuid);
            $this->integridadService->asegurarRegistroNoAplicado($comision->nomina, 'la comisión');

            if ($comision->status === 'rechazada') {
                $data = [
                    ...$data,
                    'status' => 'pendiente',
                    'autorizado_por' => null,
                    'fecha_gestion' => null,
                    'observacion_gestion' => null,
                ];
            }

            $comision->update($data);

            Log::info('Comisión actualizada', [
                'uuid' => $comision->uuid,
                'status' => $comision->status,
                'nomina_id' => $comision->nomina_id,
                'liquidacion_retiro_id' => $comision->liquidacion_retiro_id,
            ]);

            return $comision->fresh(self::WITH);
        });
    }

    public function aprobar(string $uuid, ?string $observacion = null): Comision
    {
        return $this->gestionar($uuid, 'aprobada', $observacion);
    }

    public function rechazar(string $uuid, ?string $observacion = null): Comision
    {
        return $this->gestionar($uuid, 'rechazada', $observacion);
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $comision = $this->getByUuid($uuid);
            $this->integridadService->asegurarRegistroNoAplicado($comision->nomina, 'la comisión');

            if ($comision->status !== 'pendiente') {
                throw new \LogicException('Solo se pueden eliminar comisiones pendientes.');
            }

            $comision->delete();

            Log::info('Comisión eliminada', ['uuid' => $comision->uuid]);
        });
    }

    private function gestionar(string $uuid, string $status, ?string $observacion): Comision
    {
        return DB::transaction(function () use ($uuid, $status, $observacion) {
            $comision = $this->getByUuid($uuid);
            $this->integridadService->asegurarRegistroNoAplicado($comision->nomina, 'la comisión');

            if ($comision->status !== 'pendiente') {
                throw new \LogicException("La comisión ya fue {$comision->status}.");
            }

            $comision->update([
                'status' => $status,
                'autorizado_por' => Auth::id(),
                'fecha_gestion' => now(),
                'observacion_gestion' => $observacion,
            ]);

            Log::info('Comisión gestionada', [
                'uuid' => $comision->uuid,
                'status' => $status,
                'autorizado_por' => Auth::id(),
            ]);

            return $comision->fresh(self::WITH);
        });
    }
}
