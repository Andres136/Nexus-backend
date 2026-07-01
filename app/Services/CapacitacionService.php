<?php

namespace App\Services;

use App\RolEnum;
use App\Models\Capacitacion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CapacitacionService
{
    public function getAll(array $filters, User $user)
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 25), 1), 100);

        $capacitaciones = Capacitacion::query()
            ->with('creador:id,name,email')
            ->withCount('encuestas')
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('titulo', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%")
                        ->orWhere('lugar', 'like', "%{$search}%")
                        ->orWhereHas('creador', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when(!empty($filters['fecha_desde']), fn ($query) =>
                $query->whereDate('fecha_realizacion', '>=', $filters['fecha_desde']))
            ->when(!empty($filters['fecha_hasta']), fn ($query) =>
                $query->whereDate('fecha_realizacion', '<=', $filters['fecha_hasta']))
            ->when(!empty($filters['estado']), fn ($query) =>
                $query->where('estado', $filters['estado']))
            ->when(!empty($filters['user_id']), fn ($query) =>
                $query->where('user_id', $filters['user_id']))
            ->when(!empty($filters['propias']), fn ($query) =>
                $query->where('user_id', $user->id))
            ->orderBy('fecha_realizacion')
            ->orderBy('hora_inicio')
            ->paginate($perPage);

        $capacitaciones->getCollection()->transform(
            fn ($capacitacion) => $this->marcarPermisos($capacitacion, $user)
        );

        return $capacitaciones;
    }

    public function show(string $uuid, User $user): Capacitacion
    {
        $capacitacion = Capacitacion::with('creador:id,name,email')
            ->where('uuid', $uuid)
            ->firstOrFail();

        return $this->marcarPermisos($capacitacion, $user);
    }

    public function store(array $data, User $user): Capacitacion
    {
        $capacitacion = Capacitacion::create([
            ...Arr::only($data, [
                'titulo',
                'descripcion',
                'fecha_realizacion',
                'hora_inicio',
                'hora_fin',
                'lugar',
                'modalidad',
                'estado',
            ]),
            'user_id' => $user->id,
            'modalidad' => $data['modalidad'] ?? 'presencial',
            'estado' => $data['estado'] ?? 'programada',
        ]);

        return $this->show($capacitacion->uuid, $user);
    }

    public function update(string $uuid, array $data, User $user): Capacitacion
    {
        return DB::transaction(function () use ($uuid, $data, $user) {
            $capacitacion = Capacitacion::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            $this->validarPuedeModificar($capacitacion, $user);

            $capacitacion->update(Arr::only($data, [
                'titulo',
                'descripcion',
                'fecha_realizacion',
                'hora_inicio',
                'hora_fin',
                'lugar',
                'modalidad',
                'estado',
            ]));

            return $this->show($capacitacion->uuid, $user);
        });
    }

    public function destroy(string $uuid, User $user): void
    {
        DB::transaction(function () use ($uuid, $user) {
            $capacitacion = Capacitacion::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            $this->validarPuedeEliminar($capacitacion, $user);
            $capacitacion->delete();
        });
    }

    private function validarPuedeModificar(Capacitacion $capacitacion, User $user): void
    {
        if ((int) $capacitacion->user_id !== (int) $user->id) {
            abort(403, 'Solo el usuario que creó la capacitación puede editarla.');
        }

        if ($this->fechaYaPaso($capacitacion)) {
            abort(422, 'La capacitación ya pasó y no puede editarse.');
        }
    }

    private function validarPuedeEliminar(Capacitacion $capacitacion, User $user): void
    {
        if ($this->esAdministrador($user)) {
            return;
        }

        $this->validarPuedeModificar($capacitacion, $user);
    }

    private function marcarPermisos(Capacitacion $capacitacion, User $user): Capacitacion
    {
        $capacitacion->setAttribute('puede_editar', (int) $capacitacion->user_id === (int) $user->id && !$this->fechaYaPaso($capacitacion));
        $capacitacion->setAttribute('puede_eliminar', $this->esAdministrador($user) || ((int) $capacitacion->user_id === (int) $user->id && !$this->fechaYaPaso($capacitacion)));
        $capacitacion->setAttribute('fecha_pasada', $this->fechaYaPaso($capacitacion));

        return $capacitacion;
    }

    private function esAdministrador(User $user): bool
    {
        return (int) $user->role_id === RolEnum::ADMINISTRADOR->value;
    }

    private function fechaYaPaso(Capacitacion $capacitacion): bool
    {
        return Carbon::parse($capacitacion->fecha_realizacion)->lt(today());
    }
}
