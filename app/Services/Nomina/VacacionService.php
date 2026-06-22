<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Vacacion;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VacacionService
{
    private const WITH = ['empleado:id,name,email,sede_id', 'empleado.sede:id,nombre', 'supervisor:id,name,email'];

    public function __construct(
        private readonly VacacionSaldoService $saldoService
    ) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Vacacion::with(self::WITH)
            ->when(!empty($filters['user_id']),     fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['sede_id']), fn ($q) => $q->whereHas('empleado', fn ($empleado) => $empleado->where('sede_id', $filters['sede_id'])))
            ->when(!empty($filters['search']), fn ($q) => $q->whereHas('empleado', fn ($empleado) => $empleado
                ->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('email', 'like', "%{$filters['search']}%")))
            ->when(!empty($filters['status']),      fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['tipo']),        fn($q) => $q->where('tipo', $filters['tipo']))
            ->when(!empty($filters['fecha_desde']), fn($q) => $q->whereDate('fecha_fin', '>=', $filters['fecha_desde']))
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

    public function resumen(int $userId, ?string $fechaCorte = null): array
    {
        return $this->saldoService->resumen(
            $userId,
            $fechaCorte ? Carbon::parse($fechaCorte) : null
        );
    }

    private function findByUuid(string $uuid): Vacacion
    {
        return Vacacion::with(self::WITH)->where('uuid', $uuid)->firstOrFail();
    }

    public function getDiasDisponibles(int $userId): int
    {
        return (int) floor($this->saldoService->resumen($userId)['dias_disponibles']);
    }

    public function store(array $data): Vacacion
    {
        return DB::transaction(function () use ($data) {
            $contrato = $this->saldoService->contratoActivo($data['user_id']);

            if (! $contrato) {
                throw new \LogicException('El empleado no tiene un contrato activo.');
            }

            $this->validarFechasContrato($data, $contrato);

            $saldo = $this->saldoService->resumen($data['user_id'], null, null, true);
            $this->validarCruce($data);
            $disponibles = (float) $saldo['dias_disponibles'];

            if ($data['dias_habiles'] > $disponibles) {
                throw new \LogicException(
                    "El empleado solo tiene {$disponibles} días de vacaciones disponibles."
                );
            }

            $vacacion = Vacacion::create(array_merge($data, [
                'contratacion_id' => $contrato->id,
                'status' => 'pendiente',
            ]));

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

    public function update(string $uuid, array $data): Vacacion
    {
        return DB::transaction(function () use ($uuid, $data) {
            $vacacion = $this->findByUuid($uuid);

            if ($vacacion->status === 'aprobada') {
                throw new \LogicException('No se puede editar una vacación ya aprobada.');
            }

            $contrato = $this->saldoService->contratoActivo($data['user_id']);

            if (! $contrato) {
                throw new \LogicException('El empleado no tiene un contrato activo.');
            }

            $this->validarFechasContrato($data, $contrato);

            $saldo = $this->saldoService->resumen($data['user_id'], null, $vacacion->id, true);
            $this->validarCruce($data, $vacacion->id);
            $disponibles = (float) $saldo['dias_disponibles'];

            if ($data['dias_habiles'] > $disponibles) {
                throw new \LogicException(
                    "El empleado solo tiene {$disponibles} días de vacaciones disponibles."
                );
            }

            $vacacion->update(array_merge($data, [
                'contratacion_id'     => $contrato->id,
                'status'              => 'pendiente',
                'autorizado_por'      => null,
                'fecha_gestion'       => null,
                'observacion_gestion' => null,
            ]));

            Log::info('Vacación actualizada', [
                'uuid'       => $vacacion->uuid,
                'user_id'    => $vacacion->user_id,
                'editado_por' => Auth::id(),
            ]);

            return $vacacion->fresh(self::WITH);
        });
    }

    public function aprobar(string $uuid, ?string $observacion = null): Vacacion
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $vacacion = Vacacion::where('uuid', $uuid)->lockForUpdate()->firstOrFail();

            if ($vacacion->status !== 'pendiente') {
                throw new \LogicException("La vacación ya fue {$vacacion->status}.");
            }

            $contrato = $this->saldoService->contratoActivo($vacacion->user_id);
            if (! $contrato) {
                throw new \LogicException('El empleado no tiene un contrato activo.');
            }

            $this->validarFechasContrato($vacacion->toArray(), $contrato);

            $saldo = $this->saldoService->resumen(
                $vacacion->user_id,
                null,
                $vacacion->id,
                true
            );

            if ($vacacion->dias_habiles > $saldo['dias_disponibles']) {
                throw new \LogicException(
                    "La solicitud tiene {$vacacion->dias_habiles} días, pero el empleado solo tiene "
                    ."{$saldo['dias_disponibles']} días disponibles."
                );
            }

            $vacacion->update([
                'contratacion_id'     => $vacacion->contratacion_id ?: $contrato->id,
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

    private function validarFechasContrato(array $data, $contrato): void
    {
        $inicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->startOfDay();
        $inicioContrato = Carbon::parse($contrato->inicio_contratacion)->startOfDay();

        if ($inicio->lt($inicioContrato)) {
            throw new \LogicException('Las vacaciones no pueden iniciar antes del contrato activo.');
        }

        if ($contrato->fin_contrato && $fin->gt(Carbon::parse($contrato->fin_contrato)->endOfDay())) {
            throw new \LogicException('Las vacaciones no pueden finalizar después del contrato.');
        }

        $diasCalendario = $inicio->diffInDays($fin) + 1;
        if ($data['dias_habiles'] > $diasCalendario) {
            throw new \LogicException('Los días hábiles no pueden superar los días calendario del período.');
        }
    }

    private function validarCruce(array $data, ?int $excluirId = null): void
    {
        $existeCruce = Vacacion::where('user_id', $data['user_id'])
            ->whereIn('status', ['pendiente', 'aprobada'])
            ->when($excluirId, fn ($query) => $query->where('id', '!=', $excluirId))
            ->whereDate('fecha_inicio', '<=', $data['fecha_fin'])
            ->whereDate('fecha_fin', '>=', $data['fecha_inicio'])
            ->exists();

        if ($existeCruce) {
            throw new \LogicException('Ya existe una solicitud pendiente o aprobada que se cruza con esas fechas.');
        }
    }
}
