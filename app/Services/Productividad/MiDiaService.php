<?php

namespace App\Services\Productividad;

use App\Models\Nomina\WorkSession;
use App\Models\Productividad\ActividadOperativa;
use App\Models\Productividad\JornadaOperativa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MiDiaService
{
    private const WITH_ACTIVIDAD = ['tarea:id,nombre', 'categoria:id,nombre', 'creadaPor:id,name', 'cerradaPor:id,name'];

    public function estadoDelDia(int $userId, ?string $fecha = null): array
    {
        $fecha = $fecha ?? now(config('app.timezone'))->toDateString();

        $jornada = JornadaOperativa::with('workSession')
            ->where('user_id', $userId)
            ->whereDate('fecha', $fecha)
            ->first();

        $workSession = $jornada?->workSession ?? WorkSession::where('user_id', $userId)
            ->whereDate('registro_diario', $fecha)
            ->whereNotNull('hora_entrada')
            ->first();

        $actividadActiva = $jornada
            ? ActividadOperativa::with(self::WITH_ACTIVIDAD)
                ->where('jornada_operativa_id', $jornada->id)
                ->whereIn('estado', ActividadOperativa::ESTADOS_ABIERTOS)
                ->latest('inicio_at')
                ->first()
            : null;

        return [
            'jornada' => $jornada,
            // Se marcó entrada en el kiosko pero aún no hay jornada (ninguna actividad ni "disponible" registrados): sin clasificar, no "disponible".
            'estado_actual' => $jornada?->estado_actual ?? ($workSession ? 'SIN_CLASIFICAR' : null),
            'work_session' => $workSession,
            'actividad_activa' => $actividadActiva,
            'linea_tiempo' => $this->lineaTiempo($userId, $fecha),
            'resumen' => $jornada
                ? $this->resumenDia($jornada)
                : ($workSession ? $this->resumenSesionSinJornada($workSession) : null),
        ];
    }

    /**
     * Títulos de actividades tipo TAREA que este usuario ya ha escrito antes,
     * para sugerir tareas recurrentes en vez de depender de un catálogo formal.
     */
    public function sugerenciasTarea(int $userId, ?string $busqueda = null): array
    {
        return ActividadOperativa::where('user_id', $userId)
            ->where('tipo', ActividadOperativa::TIPO_TAREA)
            ->whereNotNull('titulo')
            ->when($busqueda, fn ($q) => $q->where('titulo', 'like', "%{$busqueda}%"))
            ->select('titulo', DB::raw('COUNT(*) as usos'), DB::raw('MAX(inicio_at) as ultima_vez'))
            ->groupBy('titulo')
            ->orderByDesc('usos')
            ->orderByDesc('ultima_vez')
            ->limit(10)
            ->pluck('titulo')
            ->all();
    }

    public function lineaTiempo(int $userId, ?string $fecha = null)
    {
        $fecha = $fecha ?? now(config('app.timezone'))->toDateString();

        return ActividadOperativa::with(self::WITH_ACTIVIDAD)
            ->where('user_id', $userId)
            ->whereHas('jornadaOperativa', fn ($q) => $q->whereDate('fecha', $fecha))
            ->orderBy('inicio_at')
            ->get();
    }

    private function obtenerOCrearJornada(int $userId, Carbon $ahora): JornadaOperativa
    {
        $fecha = $ahora->toDateString();

        $jornada = JornadaOperativa::where('user_id', $userId)
            ->whereDate('fecha', $fecha)
            ->first();

        if ($jornada) {
            return $jornada;
        }

        $workSession = WorkSession::where('user_id', $userId)
            ->whereDate('registro_diario', $fecha)
            ->whereNotNull('hora_entrada')
            ->first();

        if (! $workSession) {
            throw ValidationException::withMessages([
                'jornada' => 'No tienes una jornada de kiosko abierta hoy. Marca tu entrada en el kiosko antes de usar Mi Día.',
            ]);
        }

        return JornadaOperativa::create([
            'user_id' => $userId,
            'work_session_id' => $workSession->id,
            'fecha' => $fecha,
            'estado_actual' => JornadaOperativa::DISPONIBLE,
            'iniciada_at' => $ahora,
        ]);
    }

    private function actividadAbierta(int $userId): ?ActividadOperativa
    {
        return ActividadOperativa::where('user_id', $userId)
            ->whereIn('estado', ActividadOperativa::ESTADOS_ABIERTOS)
            ->lockForUpdate()
            ->first();
    }

    private function cerrarActividad(ActividadOperativa $actividad, string $estado, int $cerradaPor, array $extra = []): void
    {
        $fin = $actividad->fin_at ?? now(config('app.timezone'));
        $actividad->update(array_merge([
            'estado' => $estado,
            'fin_at' => $fin,
            'segundos' => $actividad->segundos ?? max(0, $actividad->inicio_at->diffInSeconds($fin)),
            'cerrada_por' => $cerradaPor,
        ], $extra));
    }

    public function pausarPorKiosko(int $userId, string $motivo): ?ActividadOperativa
    {
        return DB::transaction(function () use ($userId, $motivo) {
            $actividad = ActividadOperativa::where('user_id', $userId)
                ->where('estado', ActividadOperativa::ESTADO_ACTIVA)
                ->lockForUpdate()
                ->first();

            if (! $actividad) {
                return null;
            }

            $this->cerrarActividad($actividad, ActividadOperativa::ESTADO_PAUSADA, $userId, [
                'resultado' => trim(($actividad->resultado ? $actividad->resultado."\n" : '').$motivo),
            ]);
            $actividad->jornadaOperativa()->update(['estado_actual' => JornadaOperativa::PAUSA]);

            return $actividad->fresh(self::WITH_ACTIVIDAD);
        });
    }

    public function reanudarActividad(int $userId, string $uuid): ActividadOperativa
    {
        return DB::transaction(function () use ($userId, $uuid) {
            $anterior = ActividadOperativa::where('uuid', $uuid)
                ->where('user_id', $userId)
                ->where('estado', ActividadOperativa::ESTADO_PAUSADA)
                ->lockForUpdate()
                ->firstOrFail();

            if (ActividadOperativa::where('user_id', $userId)->where('estado', ActividadOperativa::ESTADO_ACTIVA)->exists()) {
                throw ValidationException::withMessages(['actividad' => 'Ya tienes otra actividad activa.']);
            }

            $anterior->update(['estado' => ActividadOperativa::ESTADO_INTERRUMPIDA]);

            $nueva = ActividadOperativa::create([
                'jornada_operativa_id' => $anterior->jornada_operativa_id,
                'user_id' => $userId,
                'tipo' => $anterior->tipo,
                'tarea_id' => $anterior->tarea_id,
                'categoria_id' => $anterior->categoria_id,
                'titulo' => $anterior->titulo,
                'descripcion' => $anterior->descripcion,
                'estado' => ActividadOperativa::ESTADO_ACTIVA,
                'inicio_at' => now(config('app.timezone')),
                'creada_por' => $userId,
            ]);
            $anterior->jornadaOperativa()->update(['estado_actual' => JornadaOperativa::EN_ACTIVIDAD]);

            return $nueva->load(self::WITH_ACTIVIDAD);
        });
    }

    public function iniciarActividad(int $userId, array $data): ActividadOperativa
    {
        return DB::transaction(function () use ($userId, $data) {
            $ahora = now(config('app.timezone'));

            if ($this->actividadAbierta($userId)) {
                throw ValidationException::withMessages([
                    'actividad' => 'Ya tienes una actividad activa. Complétala, bloquéala o márcate disponible antes de iniciar otra.',
                ]);
            }

            $jornada = $this->obtenerOCrearJornada($userId, $ahora);

            $actividad = ActividadOperativa::create([
                'jornada_operativa_id' => $jornada->id,
                'user_id' => $userId,
                'tipo' => $data['tipo'],
                'tarea_id' => $data['tarea_id'] ?? null,
                'categoria_id' => $data['categoria_id'] ?? null,
                'titulo' => $data['titulo'] ?? null,
                'descripcion' => $data['descripcion'] ?? null,
                'estado' => ActividadOperativa::ESTADO_ACTIVA,
                'inicio_at' => $ahora,
                'creada_por' => $userId,
            ]);

            $jornada->update(['estado_actual' => JornadaOperativa::EN_ACTIVIDAD]);

            Log::info('Actividad operativa iniciada', ['uuid' => $actividad->uuid, 'user_id' => $userId]);

            return $actividad->load(self::WITH_ACTIVIDAD);
        });
    }

    public function marcarDisponible(int $userId): ActividadOperativa
    {
        return DB::transaction(function () use ($userId) {
            $ahora = now(config('app.timezone'));
            $jornada = $this->obtenerOCrearJornada($userId, $ahora);

            $abierta = $this->actividadAbierta($userId);
            if ($abierta) {
                $this->cerrarActividad($abierta, ActividadOperativa::ESTADO_COMPLETADA, $userId);
            }

            $disponible = ActividadOperativa::create([
                'jornada_operativa_id' => $jornada->id,
                'user_id' => $userId,
                'tipo' => ActividadOperativa::TIPO_DISPONIBLE,
                'estado' => ActividadOperativa::ESTADO_ACTIVA,
                'inicio_at' => $ahora,
                'creada_por' => $userId,
            ]);

            $jornada->update(['estado_actual' => JornadaOperativa::DISPONIBLE]);

            return $disponible->load(self::WITH_ACTIVIDAD);
        });
    }

    public function completarActividad(int $userId, string $uuid, array $data): ActividadOperativa
    {
        return DB::transaction(function () use ($userId, $uuid, $data) {
            $actividad = ActividadOperativa::where('uuid', $uuid)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($actividad->estado, ActividadOperativa::ESTADOS_ABIERTOS, true)) {
                throw ValidationException::withMessages([
                    'actividad' => 'Esta actividad ya fue cerrada y no puede completarse de nuevo.',
                ]);
            }

            $this->cerrarActividad($actividad, ActividadOperativa::ESTADO_COMPLETADA, $userId, [
                'resultado' => $data['resultado'],
                'descripcion' => $data['observacion'] ?? $actividad->descripcion,
            ]);

            $actividad->jornadaOperativa->update(['estado_actual' => JornadaOperativa::DISPONIBLE]);

            Log::info('Actividad operativa completada', ['uuid' => $uuid, 'user_id' => $userId]);

            return $actividad->fresh(self::WITH_ACTIVIDAD);
        });
    }

    public function bloquearActividad(int $userId, string $uuid, array $data): ActividadOperativa
    {
        return DB::transaction(function () use ($userId, $uuid, $data) {
            $actividad = ActividadOperativa::where('uuid', $uuid)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($actividad->estado, ActividadOperativa::ESTADOS_ABIERTOS, true)) {
                throw ValidationException::withMessages([
                    'actividad' => 'Esta actividad ya fue cerrada y no puede bloquearse.',
                ]);
            }

            $this->cerrarActividad($actividad, ActividadOperativa::ESTADO_BLOQUEADA, $userId, [
                'motivo_bloqueo' => $data['motivo_bloqueo'],
            ]);

            $actividad->jornadaOperativa->update(['estado_actual' => JornadaOperativa::DISPONIBLE]);

            Log::info('Actividad operativa bloqueada', ['uuid' => $uuid, 'user_id' => $userId]);

            return $actividad->fresh(self::WITH_ACTIVIDAD);
        });
    }

    public function cancelarActividad(int $userId, string $uuid): ActividadOperativa
    {
        return DB::transaction(function () use ($userId, $uuid) {
            $actividad = ActividadOperativa::where('uuid', $uuid)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($actividad->estado, ActividadOperativa::ESTADOS_ABIERTOS, true)) {
                throw ValidationException::withMessages([
                    'actividad' => 'Esta actividad ya fue cerrada y no puede cancelarse.',
                ]);
            }

            $this->cerrarActividad($actividad, ActividadOperativa::ESTADO_CANCELADA, $userId);
            $actividad->jornadaOperativa->update(['estado_actual' => JornadaOperativa::DISPONIBLE]);

            return $actividad->fresh(self::WITH_ACTIVIDAD);
        });
    }

    /**
     * tiempo_clasificable = jornada - pausas_kiosko - almuerzo
     * tiempo_clasificado  = tareas + otras_actividades + disponible
     * tiempo_sin_clasificar = max(0, clasificable - clasificado)
     */
    public function resumenDia(JornadaOperativa $jornada): array
    {
        $workSession = $jornada->workSession;

        $minutosJornada = 0;
        if ($workSession?->hora_entrada) {
            $fin = $workSession->hora_salida ?? now(config('app.timezone'));
            $minutosJornada = max(0, (int) Carbon::parse($workSession->hora_entrada)->diffInMinutes(Carbon::parse($fin)));
        }

        $minutosPausa = (int) ($workSession->minutos_pausa ?? 0);
        $minutosAlmuerzo = (int) ($workSession->minutos_almuerzo ?? 0);
        $tiempoClasificable = max(0, $minutosJornada - $minutosPausa - $minutosAlmuerzo);

        $actividades = $jornada->actividades;

        $segundosPorTipo = fn (string $tipo) => (int) $actividades
            ->where('tipo', $tipo)
            ->sum(fn (ActividadOperativa $a) => $a->segundos ?? ($a->fin_at ? 0 : $a->inicio_at->diffInSeconds(now(config('app.timezone')))));

        $minutosTarea = (int) round($segundosPorTipo(ActividadOperativa::TIPO_TAREA) / 60);
        $minutosOtra = (int) round($segundosPorTipo(ActividadOperativa::TIPO_OTRA_ACTIVIDAD) / 60);
        $minutosDisponible = (int) round($segundosPorTipo(ActividadOperativa::TIPO_DISPONIBLE) / 60);

        $tiempoClasificado = $minutosTarea + $minutosOtra + $minutosDisponible;
        $tiempoSinClasificar = max(0, $tiempoClasificable - $tiempoClasificado);

        return [
            'minutos_jornada' => $minutosJornada,
            'minutos_pausa' => $minutosPausa,
            'minutos_almuerzo' => $minutosAlmuerzo,
            'minutos_clasificable' => $tiempoClasificable,
            'minutos_tarea' => $minutosTarea,
            'minutos_otra_actividad' => $minutosOtra,
            'minutos_disponible' => $minutosDisponible,
            'minutos_clasificado' => $tiempoClasificado,
            'minutos_sin_clasificar' => $tiempoSinClasificar,
            'minutos_parado' => $tiempoSinClasificar,
        ];
    }

    /**
     * Mismo cálculo que resumenDia() pero para cuando ya hay entrada de kiosko y
     * todavía no existe JornadaOperativa: todo el tiempo desde hora_entrada (menos
     * pausas/almuerzo del kiosko) cuenta como tiempo muerto, porque nada se ha clasificado aún.
     */
    public function resumenSesionSinJornada(WorkSession $workSession): array
    {
        $fin = $workSession->hora_salida ?? now(config('app.timezone'));
        $minutosJornada = max(0, (int) Carbon::parse($workSession->hora_entrada)->diffInMinutes(Carbon::parse($fin)));
        $minutosPausa = (int) ($workSession->minutos_pausa ?? 0);
        $minutosAlmuerzo = (int) ($workSession->minutos_almuerzo ?? 0);
        $minutosParado = max(0, $minutosJornada - $minutosPausa - $minutosAlmuerzo);

        return [
            'minutos_jornada' => $minutosJornada,
            'minutos_pausa' => $minutosPausa,
            'minutos_almuerzo' => $minutosAlmuerzo,
            'minutos_clasificable' => $minutosParado,
            'minutos_tarea' => 0,
            'minutos_otra_actividad' => 0,
            'minutos_disponible' => 0,
            'minutos_clasificado' => 0,
            'minutos_sin_clasificar' => $minutosParado,
            'minutos_parado' => $minutosParado,
        ];
    }
}
