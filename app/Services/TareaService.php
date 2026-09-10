<?php

namespace App\Services;

use App\Models\Tareas;
use App\Models\TareaSeguimiento;
use App\Models\User;
use App\Notifications\NuevaTareaAsignada;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TareaService
{
    public function listar(Request $request, User $user): Collection
    {
        $query = Tareas::with('usuario', 'creador', 'departamentos')
            ->whereIn('estado_id', [1, 5,]);

        $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('user_id_creo', $user->id);
        });

        if ($request->filled('usuario')) {
            $query->whereHas('usuario', fn($q) =>
                $q->where('name', 'like', '%' . $request->usuario . '%')
            );
        }

        if ($request->filled('departamento')) {
            $query->whereHas('departamentos', fn($q) =>
                $q->where('name', 'like', '%' . $request->departamento . '%')
            );
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Tareas cerradas donde el usuario participó (como responsable o como quien la asignó).
     */
    public function listarCompletadas(Request $request, User $user): LengthAwarePaginator
    {
        $query = Tareas::with('usuario:id,name', 'creador:id,name', 'departamentos:id,nombre')
            ->where('estado_id', 2)
            ->where(function (Builder $q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('user_id_creo', $user->id);
            });

        $this->aplicarFiltros($query, $request);

        return $query->orderBy('fecha_cerrado', 'desc')
            ->paginate($request->integer('per_page', 15));
    }

    /**
     * Tareas que el usuario asignó a otros, en cualquier estado.
     */
    public function listarAsignadas(Request $request, User $user): LengthAwarePaginator
    {
        $query = Tareas::with('usuario:id,name', 'departamentos:id,nombre')
            ->where('user_id_creo', $user->id);

        $this->aplicarFiltros($query, $request);

        return $query->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));
    }

    private function aplicarFiltros(Builder $query, Request $request): void
    {
        if ($request->filled('usuario')) {
            $query->whereHas('usuario', fn($q) =>
                $q->where('name', 'like', '%' . $request->usuario . '%')
            );
        }

        if ($request->filled('departamento_id')) {
            $query->where('departamento_id', $request->departamento_id);
        }

        if ($request->filled('estado_id')) {
            $query->where('estado_id', $request->estado_id);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }

        if ($request->filled('search')) {
            $query->where(function (Builder $q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
            });
        }
    }

    public function crear(array $data, int $creadorId): Tareas
    {
        $tarea = Tareas::create([
            'nombre'         => $data['nombre'],
            'descripcion'    => $data['descripcion'],
            'fecha_fin'      => $data['fecha_fin'],
            'estado_id'      => 1,
            'departamento_id'=> $data['departamento_id'],
            'user_id'        => $data['user_id'],
            'user_id_creo'   => $creadorId,
        ]);

        User::findOrFail($data['user_id'])->notify(new NuevaTareaAsignada($tarea));

        return $tarea;
    }

    public function obtener(int $id): Tareas
    {
        return Tareas::with('usuario', 'creador', 'departamentos')->findOrFail($id);
    }

    /** @throws AuthorizationException */
    public function avanzarEstado(int $id, User $user, ?string $nota): Tareas
    {
        $tarea = Tareas::findOrFail($id);

        $estadoAnterior = $tarea->estado_id;

        if ($estadoAnterior == 1) {
            if ($tarea->soportes()->count() === 0) {
                throw new AuthorizationException('Debes adjuntar al menos un soporte antes de pasar la tarea a revisión');
            }
            $tarea->estado_id = 5;
        } elseif ($estadoAnterior == 5) {
            if (!in_array($user->role_id, [1, 20]) && $tarea->user_id_creo !== $user->id) {
                throw new AuthorizationException('Solo el usuario que registró la tarea puede cerrarla');
            }
            $tarea->estado_id = 2;
            $tarea->fecha_cerrado = now();
        }

        $tarea->save();

        TareaSeguimiento::create([
            'tarea_id'        => $tarea->id,
            'user_id'         => $user->id,
            'nota'            => $nota,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo'    => $tarea->estado_id,
            'tipo'            => 'cambio_estado',
        ]);

        return $tarea;
    }

    public function actualizar(int $id, array $data): Tareas
    {
    $user = auth()->user();    
    $tarea = Tareas::findOrFail($id);
   
        $tarea->fill(array_filter([
            'nombre'         => $data['nombre'] ?? null,
            'descripcion'    => $data['descripcion'] ?? null,
            'fecha_fin'      => $data['fecha_fin'] ?? null,
            'departamento_id'=> $data['departamento_id'] ?? null,
            'user_id'        => $data['user_id'] ?? null,
            'user_id_creo'   => $user->id,
        ], fn($v) => !is_null($v)))->save();

        return $tarea;
    }

    public function lineaTiempo(): Collection
    {
        return Tareas::with('usuario')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /** @throws AuthorizationException */
    public function agregarNota(int $id, User $user, string $nota): TareaSeguimiento
    {
        $tarea = Tareas::findOrFail($id);

        if (!in_array($user->role_id, [1, 20]) && !in_array($user->id, [$tarea->user_id, $tarea->user_id_creo])) {
            throw new AuthorizationException('No tienes permiso para agregar notas a esta tarea');
        }

        return TareaSeguimiento::create([
            'tarea_id' => $tarea->id,
            'user_id'  => $user->id,
            'nota'     => $nota,
            'tipo'     => 'nota',
        ]);
    }

    public function historial(int $id): Collection
    {
        Tareas::findOrFail($id);

        return TareaSeguimiento::with('usuario:id,name')
            ->where('tarea_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function resumenMensualFiltrado(Request $request): array
    {
        $query = Tareas::with('usuario', 'departamentos');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('departamento_id')) {
            $query->where('departamento_id', $request->departamento_id);
        }

        $tareas = $query->get();

        $resumen = $tareas
            ->groupBy(fn($t) => Carbon::parse($t->created_at)->month)
            ->map(fn($group, $mes) => [
                'mes'              => $mes,
                'total'            => $group->count(),
                'pendientes'       => $group->where('estado_id', 1)->count(),
                'completadas'      => $group->where('estado_id', 2)->count(),
                'user_ids'         => $group->pluck('user_id')->unique()->values(),
                'departamento_ids' => $group->pluck('departamento_id')->unique()->values(),
            ])->values();

        $usuarios = $tareas->pluck('usuario')->filter()->unique('id')->values()
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name]);

        $departamentos = $tareas->pluck('departamentos')->filter()->unique('id')->values()
            ->map(fn($d) => ['id' => $d->id, 'nombre' => $d->nombre]);

        return compact('resumen', 'usuarios', 'departamentos');
    }
}
