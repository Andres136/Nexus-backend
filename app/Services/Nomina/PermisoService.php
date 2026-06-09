<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Permiso;
use App\RolEnum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PermisoService
{
    private const WITH = ['empleado:id,name,email', 'supervisor:id,name,email'];

    private const ROLES_PRIVILEGIADOS = [RolEnum::ADMINISTRADOR, RolEnum::ADMINISTRATIVO];

    private function rolesPrivilegiadosIds(): array
    {
        return array_map(fn($rol) => $rol->value, self::ROLES_PRIVILEGIADOS);
    }

    private function usuarioAutenticado()
    {
        $user = Auth::user();

        if (!$user) {
            throw new AuthorizationException('Usuario no autenticado.');
        }

        return $user;
    }

    private function esPrivilegiado($user): bool
    {
        return in_array((int) $user->role_id, $this->rolesPrivilegiadosIds(), true);
    }

    private function asegurarPrivilegiado(): void
    {
        if (!$this->esPrivilegiado($this->usuarioAutenticado())) {
            throw new AuthorizationException('No tienes permiso para gestionar solicitudes de permiso.');
        }
    }

    private function resolverUserId(array &$filters): void
    {
        $user = $this->usuarioAutenticado();
        if (!$this->esPrivilegiado($user)) {
            $filters['user_id'] = $user->id;
        }
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $this->resolverUserId($filters);
        $perPage = $filters['per_page'] ?? 15;

        return Permiso::with(self::WITH)
            ->when(!empty($filters['user_id']),    fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['status']),     fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['tipo']),       fn($q) => $q->where('tipo', $filters['tipo']))
            ->when(!empty($filters['fecha_desde']),fn($q) => $q->whereDate('fecha', '>=', $filters['fecha_desde']))
            ->when(!empty($filters['fecha_hasta']),fn($q) => $q->whereDate('fecha', '<=', $filters['fecha_hasta']))
            ->orderByDesc('fecha')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): Permiso
    {
        return Permiso::with(self::WITH)->where('uuid', $uuid)->firstOrFail();
    }

    public function store(array $data): Permiso
    {
        return DB::transaction(function () use ($data) {
            $data['user_id'] = $this->usuarioAutenticado()->id;

            if (empty($data['user_id'])) {
                throw ValidationException::withMessages([
                    'user_id' => 'El empleado es obligatorio.',
                ]);
            }

            $permiso = Permiso::create(array_merge($data, ['status' => 'pendiente']));

            Log::info('Permiso registrado', [
                'uuid'    => $permiso->uuid,
                'user_id' => $permiso->user_id,
                'fecha'   => $permiso->fecha,
                'tipo'    => $permiso->tipo,
                'minutos' => $permiso->minutos,
            ]);

            return $permiso->load(self::WITH);
        });
    }

    public function aprobar(string $uuid, ?string $observacion = null): Permiso
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $this->asegurarPrivilegiado();
            $permiso = $this->getByUuid($uuid);

            if ($permiso->status !== 'pendiente') {
                throw new \LogicException("El permiso ya fue {$permiso->status}.");
            }

            $permiso->update([
                'status'              => 'aprobado',
                'autorizado_por'      => Auth::id(),
                'fecha_gestion'       => now(),
                'observacion_gestion' => $observacion,
            ]);

            Log::info('Permiso aprobado', [
                'uuid'          => $permiso->uuid,
                'user_id'       => $permiso->user_id,
                'es_remunerado' => $permiso->es_remunerado,
                'autorizado_por'=> Auth::id(),
            ]);

            return $permiso->fresh(self::WITH);
        });
    }

    public function rechazar(string $uuid, ?string $observacion = null): Permiso
    {
        return DB::transaction(function () use ($uuid, $observacion) {
            $this->asegurarPrivilegiado();
            $permiso = $this->getByUuid($uuid);

            if ($permiso->status !== 'pendiente') {
                throw new \LogicException("El permiso ya fue {$permiso->status}.");
            }

            $permiso->update([
                'status'              => 'rechazado',
                'autorizado_por'      => Auth::id(),
                'fecha_gestion'       => now(),
                'observacion_gestion' => $observacion,
            ]);

            Log::info('Permiso rechazado', [
                'uuid'    => $permiso->uuid,
                'user_id' => $permiso->user_id,
            ]);

            return $permiso->fresh(self::WITH);
        });
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $permiso = $this->getByUuid($uuid);

            if ($permiso->status === 'aprobado') {
                throw new \LogicException('No se puede eliminar un permiso ya aprobado.');
            }

            $permiso->delete();

            Log::info('Permiso eliminado', ['uuid' => $permiso->uuid]);
        });
    }
}
