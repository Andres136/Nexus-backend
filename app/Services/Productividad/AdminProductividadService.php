<?php

namespace App\Services\Productividad;

use App\Models\Nomina\WorkSession;
use App\Models\Productividad\ActividadOperativa;
use App\Models\Productividad\CorreccionActividadOperativa;
use App\Models\Productividad\EventoProductividad;
use App\Models\Productividad\JornadaOperativa;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminProductividadService
{
    private const CAMPOS_CORREGIBLES = [
        'estado', 'segundos', 'titulo', 'descripcion', 'resultado', 'motivo_bloqueo',
    ];

    public function __construct(private readonly MiDiaService $miDiaService)
    {
    }

    public function equipoDelDia(array $filtros): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filtros['per_page'] ?? 15)));

        $paginador = $this->baseQuery($filtros)
            ->orderBy('work_sessions.hora_entrada')
            ->paginate($perPage)
            ->withQueryString();

        $jornadaIds = $paginador->getCollection()->pluck('jornada_operativa_id')->filter()->all();

        $jornadas = JornadaOperativa::whereIn('id', $jornadaIds)
            ->with(['actividades' => fn ($q) => $q->with(['tarea:id,nombre', 'categoria:id,nombre'])])
            ->get()
            ->keyBy('id');

        $paginador->setCollection(
            $paginador->getCollection()->map(
                fn (WorkSession $ws) => $this->mapFila($ws, $jornadas->get($ws->jornada_operativa_id))
            )
        );

        return $paginador;
    }

    public function filasParaExportar(array $filtros): Collection
    {
        $registros = $this->baseQuery($filtros)
            ->orderBy('work_sessions.hora_entrada')
            ->limit(1000)
            ->get();

        $jornadaIds = $registros->pluck('jornada_operativa_id')->filter()->all();

        $jornadas = JornadaOperativa::whereIn('id', $jornadaIds)
            ->with('actividades')
            ->get()
            ->keyBy('id');

        return $registros->map(fn (WorkSession $ws) => $this->mapFila($ws, $jornadas->get($ws->jornada_operativa_id)));
    }

    public function detalleUsuario(int $userId, string $fechaInicio, string $fechaFin): array
    {
        $usuario = User::select('id', 'name', 'email')->findOrFail($userId);

        $sesiones = WorkSession::query()
            ->where('user_id', $userId)
            ->whereBetween('registro_diario', [$fechaInicio, $fechaFin])
            ->whereNotNull('hora_entrada')
            ->orderBy('registro_diario')
            ->get();

        $jornadas = JornadaOperativa::query()
            ->where('user_id', $userId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->with([
                'actividades' => fn ($q) => $q
                    ->with(['tarea:id,nombre', 'categoria:id,nombre'])
                    ->orderBy('inicio_at'),
                'eventos' => fn ($q) => $q->orderBy('ocurrio_at'),
            ])
            ->get();

        $jornadasPorSesion = $jornadas->whereNotNull('work_session_id')->keyBy('work_session_id');
        $jornadasSinSesion = $jornadas->whereNull('work_session_id')->keyBy(fn ($j) => $j->fecha->toDateString());
        $dias = collect();

        foreach ($sesiones as $sesion) {
            $jornada = $jornadasPorSesion->get($sesion->id);
            $resumen = $jornada
                ? $this->miDiaService->resumenDia($jornada->setRelation('workSession', $sesion))
                : $this->miDiaService->resumenSesionSinJornada($sesion);

            $dias->push($this->mapDetalleDia($sesion->registro_diario->toDateString(), $sesion, $jornada, $resumen));
        }

        $fechasConSesion = $sesiones->pluck('registro_diario')->map->toDateString()->flip();
        foreach ($jornadasSinSesion as $fecha => $jornada) {
            if (! $fechasConSesion->has($fecha)) {
                $dias->push($this->mapDetalleDia($fecha, null, $jornada, $this->miDiaService->resumenDia($jornada)));
            }
        }

        $dias = $dias->sortBy('fecha')->values();
        $lineaTiempo = $jornadas->flatMap->actividades->sortBy('inicio_at')->values();

        $tareas = [
            'pendientes' => $lineaTiempo->where('tipo', ActividadOperativa::TIPO_TAREA)
                ->whereIn('estado', ActividadOperativa::ESTADOS_ABIERTOS)->count(),
            'completadas' => $lineaTiempo->where('tipo', ActividadOperativa::TIPO_TAREA)
                ->where('estado', ActividadOperativa::ESTADO_COMPLETADA)->count(),
            'bloqueadas' => $lineaTiempo->where('tipo', ActividadOperativa::TIPO_TAREA)
                ->where('estado', ActividadOperativa::ESTADO_BLOQUEADA)->count(),
        ];

        $correcciones = CorreccionActividadOperativa::where('user_id', $userId)
            ->whereHas('actividadOperativa.jornadaOperativa', fn ($q) => $q->whereBetween('fecha', [$fechaInicio, $fechaFin]))
            ->with('registradoPor:id,name')
            ->latest()
            ->get();

        $eventos = EventoProductividad::where('user_id', $userId)
            ->whereBetween('ocurrio_at', ["{$fechaInicio} 00:00:00", "{$fechaFin} 23:59:59"])
            ->orderByDesc('ocurrio_at')
            ->get();

        $totales = $this->sumarResumenes($dias->pluck('resumen'));
        $totales['porcentaje_clasificado'] = $totales['minutos_clasificable'] > 0
            ? round(($totales['minutos_clasificado'] / $totales['minutos_clasificable']) * 100, 2)
            : null;

        return [
            'usuario' => $usuario,
            'periodo' => ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin],
            'totales' => $totales,
            'dias' => $dias,
            'linea_tiempo' => $lineaTiempo,
            'tareas' => $tareas,
            'eventos' => $eventos,
            'correcciones' => $correcciones,
        ];
    }


    private function mapDetalleDia(string $fecha, ?WorkSession $sesion, ?JornadaOperativa $jornada, array $resumen): array
    {
        return [
            'fecha' => $fecha,
            'provisional' => $sesion !== null && $sesion->hora_salida === null,
            'work_session' => $sesion ? [
                'hora_entrada' => $sesion->hora_entrada,
                'hora_salida' => $sesion->hora_salida,
            ] : null,
            'estado' => $jornada?->estado_actual ?? 'SIN_CLASIFICAR',
            'resumen' => $resumen,
            'actividades' => $jornada?->actividades ?? collect(),
            'eventos' => $jornada?->eventos ?? collect(),
        ];
    }

    private function sumarResumenes(Collection $resumenes): array
    {
        $campos = [
            'minutos_jornada', 'minutos_pausa', 'minutos_almuerzo',
            'minutos_clasificable', 'minutos_tarea', 'minutos_otra_actividad',
            'minutos_disponible', 'minutos_clasificado', 'minutos_sin_clasificar',
            'minutos_parado',
        ];

        return collect($campos)->mapWithKeys(
            fn ($campo) => [$campo => (int) $resumenes->sum(fn ($resumen) => $resumen[$campo] ?? 0)]
        )->all();
    }

    public function corregirActividad(int $adminUserId, string $uuid, array $data): array
    {
        return DB::transaction(function () use ($adminUserId, $uuid, $data) {
            $actividad = ActividadOperativa::where('uuid', $uuid)->lockForUpdate()->firstOrFail();

            $datosNuevos = collect($data['datos_nuevos'])
                ->only(self::CAMPOS_CORREGIBLES)
                ->reject(fn ($valor) => $valor === null || $valor === '')
                ->all();

            if (empty($datosNuevos)) {
                throw ValidationException::withMessages([
                    'datos_nuevos' => 'Debes indicar al menos un dato para corregir.',
                ]);
            }

            $datosAnteriores = collect(self::CAMPOS_CORREGIBLES)
                ->mapWithKeys(fn ($campo) => [$campo => $actividad->{$campo}])
                ->only(array_keys($datosNuevos))
                ->all();

            $actividad->update($datosNuevos);

            if (
                isset($datosNuevos['estado'])
                && ! in_array($datosNuevos['estado'], ActividadOperativa::ESTADOS_ABIERTOS, true)
            ) {
                $actividad->jornadaOperativa()->update(['estado_actual' => JornadaOperativa::DISPONIBLE]);
            }

            $correccion = CorreccionActividadOperativa::create([
                'actividad_operativa_id' => $actividad->id,
                'user_id' => $actividad->user_id,
                'motivo' => $data['motivo'],
                'observaciones' => $data['observaciones'] ?? null,
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => $datosNuevos,
                'registrado_por' => $adminUserId,
            ]);

            Log::info('Actividad operativa corregida por administrador', [
                'uuid' => $uuid,
                'admin_id' => $adminUserId,
                'motivo' => $data['motivo'],
            ]);

            return [
                'actividad' => $actividad->fresh(['tarea:id,nombre', 'categoria:id,nombre']),
                'correccion' => $correccion,
            ];
        });
    }

    private function baseQuery(array $filtros): Builder
    {
        $fecha = $filtros['fecha'] ?? now(config('app.timezone'))->toDateString();
        $estado = $filtros['estado'] ?? null;
        $search = $filtros['search'] ?? null;

        return WorkSession::query()
            ->select('work_sessions.*')
            ->selectRaw("COALESCE(jo.estado_actual, 'SIN_CLASIFICAR') as estado_calculado")
            ->addSelect('jo.id as jornada_operativa_id')
            ->leftJoin('jornadas_operativas as jo', function ($join) {
                $join->on('jo.work_session_id', '=', 'work_sessions.id')->whereNull('jo.deleted_at');
            })
            ->whereDate('work_sessions.registro_diario', $fecha)
            ->whereNotNull('work_sessions.hora_entrada')
            ->with('empleado:id,name,email,role_id')
            ->when($search, function ($q) use ($search) {
                $q->whereHas('empleado', function ($e) use ($search) {
                    $e->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($estado, function ($q) use ($estado) {
                $q->whereRaw("COALESCE(jo.estado_actual, 'SIN_CLASIFICAR') = ?", [$estado]);
            });
    }

    private function mapFila(WorkSession $ws, ?JornadaOperativa $jornada): array
    {
        $actividades = $jornada?->actividades ?? collect();

        $actividadActual = $actividades
            ->whereIn('estado', ActividadOperativa::ESTADOS_ABIERTOS)
            ->sortByDesc('inicio_at')
            ->first();

        $tareas = $actividades->where('tipo', ActividadOperativa::TIPO_TAREA);

        return [
            'usuario' => $ws->empleado,
            'estado' => $ws->estado_calculado,
            'work_session' => [
                'hora_entrada' => $ws->hora_entrada,
                'hora_salida' => $ws->hora_salida,
            ],
            'actividad_actual' => $actividadActual ? [
                'uuid' => $actividadActual->uuid,
                'tipo' => $actividadActual->tipo,
                'titulo' => $actividadActual->titulo,
                'categoria' => $actividadActual->categoria,
                'inicio_at' => $actividadActual->inicio_at,
                'tiempo_transcurrido_segundos' => max(0, $actividadActual->inicio_at->diffInSeconds(now(config('app.timezone')))),
            ] : null,
            'tareas' => [
                'pendientes' => $tareas->whereIn('estado', ActividadOperativa::ESTADOS_ABIERTOS)->count(),
                'completadas' => $tareas->where('estado', ActividadOperativa::ESTADO_COMPLETADA)->count(),
                'bloqueadas' => $tareas->where('estado', ActividadOperativa::ESTADO_BLOQUEADA)->count(),
            ],
            'resumen' => $jornada ? $this->miDiaService->resumenDia($jornada) : null,
        ];
    }
}
