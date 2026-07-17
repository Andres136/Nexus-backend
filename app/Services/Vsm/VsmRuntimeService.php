<?php

namespace App\Services\Vsm;

use App\Models\Vsm\Alistamiento;
use App\Models\Vsm\AlistamientoUsuarioDetalle;
use App\Models\Vsm\VsmConfiguracion;
use App\Models\Productividad\JornadaOperativa;
use App\Models\Nomina\WorkSession;
use App\Services\Productividad\MiDiaService;
use Carbon\Carbon;

class VsmRuntimeService
{
    /**
     * Distribuir el tiempo total del alistamiento entre sus detalles proporcionalmente.
     */
    public function distribuirTiempoPorDetalles(Alistamiento $alist): void
    {
        $tiempoTotal = $alist->duracion_segundos ?? 0;
        $detalles    = $alist->detalles;

        if ($tiempoTotal <= 0 || $detalles->isEmpty()) {
            return;
        }

        $totalUnidades = $detalles->sum(function ($d) {
            return $d->cantidad_alistada > 0 ? $d->cantidad_alistada : $d->cantidad_programada;
        });

        if ($totalUnidades <= 0) {
            return;
        }

        $acumulado = 0;
        $ultimo    = $detalles->count() - 1;

        foreach ($detalles as $index => $d) {
            $base = $d->cantidad_alistada > 0 ? $d->cantidad_alistada : $d->cantidad_programada;

            if ($base <= 0) {
                $d->tiempo_parcial_segundos = 0;
                $d->save();
                continue;
            }

            if ($index === $ultimo) {
                $tParcial = $tiempoTotal - $acumulado;
            } else {
                $proporcion = $base / $totalUnidades;
                $tParcial   = (int) round($tiempoTotal * $proporcion);
                $acumulado += $tParcial;
            }

            $d->tiempo_parcial_segundos = $tParcial;
            $d->save();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RENDIMIENTO INDIVIDUAL AGREGADO (tabla + KPI cards)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna el rendimiento por usuario agregado para el rango de fechas.
     * Compara producción real vs meta horaria × horas trabajadas (eficiencia real).
     */
    public function obtenerEficienciaPersonal(array $filtros): array
    {
        [$sedeId, $fechaInicio, $fechaFin] = $this->resolverFiltrosBase($filtros);

        $alistamientos = $this->queryAlistamientos($sedeId, $fechaInicio, $fechaFin)->get();

        // Fix N+1: cargar toda la producción de una sola query
        $produccionMap = $this->cargarProduccionMap($alistamientos->pluck('id')->all());

        $resultado = [];

        foreach ($alistamientos as $alist) {
            $pausasPorUsuario = $this->calcularPausasPorUsuario($alist);
            foreach ($alist->usuarios as $usuario) {
                if ($sedeId && $usuario->sede_id != $sedeId) continue;

                $userId     = $usuario->id;
                $produccion = $produccionMap["{$alist->id}_{$userId}"] ?? 0;

                if (!isset($resultado[$userId])) {
                    $horasSemanales = (float) ($usuario->pivot->horas_semanales_snapshot ?? VsmConfiguracion::metas()['horas_semanales']);
                    $resultado[$userId] = [
                        'usuario_id'            => $userId,
                        'nombre'                => $usuario->name,
                        'produccion_total'      => 0,
                        'tiempo_total_segundos' => 0,
                        'tiempo_pausa_segundos' => 0,
                        'tiempo_apoyo_operativo_segundos' => 0,
                        'actividades_apoyo_operativo' => [],
                        'motivos_pausa' => [],
                        'horas_semanales_jornada' => $horasSemanales,
                        'origen_jornada' => $usuario->pivot->horas_semanales_snapshot !== null ? 'NOMINA' : 'VSM',
                    ];
                }

                $resultado[$userId]['produccion_total']      += $produccion;
                $tiempoUsuario = max(0, (int) $usuario->pivot->tiempo_segundos);
                if ($alist->tipo_origen === 'LIBRE' && $alist->nombre_actividad) {
                    $resultado[$userId]['tiempo_apoyo_operativo_segundos'] += $tiempoUsuario;
                    $resultado[$userId]['actividades_apoyo_operativo'][] = [
                        'alistamiento_id' => $alist->id,
                        'nombre' => $alist->nombre_actividad,
                        'segundos' => $tiempoUsuario,
                    ];
                } else {
                    $resultado[$userId]['tiempo_total_segundos'] += $tiempoUsuario;
                }
                $pausasUsuario = $pausasPorUsuario[$userId] ?? ['segundos' => 0, 'motivos' => []];
                $resultado[$userId]['tiempo_pausa_segundos'] += $pausasUsuario['segundos'];

                foreach ($pausasUsuario['motivos'] as $motivo => $segundos) {
                    $resultado[$userId]['motivos_pausa'][$motivo] =
                        ($resultado[$userId]['motivos_pausa'][$motivo] ?? 0) + $segundos;
                }
            }
        }

        $metaHora = VsmConfiguracion::metaVigente(705.88);

        $tiempoMuertoPorUsuario = [];
        if ($resultado) {
            $resumenService = app(MiDiaService::class);
            $sesiones = WorkSession::whereIn('user_id', array_keys($resultado))
                ->whereBetween('registro_diario', [$fechaInicio, $fechaFin])
                ->whereNotNull('hora_entrada')
                ->get();
            $jornadas = JornadaOperativa::whereIn('work_session_id', $sesiones->pluck('id'))
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->with(['workSession', 'actividades'])
                ->get()
                ->keyBy('work_session_id');

            foreach ($sesiones as $sesion) {
                $jornada = $jornadas->get($sesion->id);
                if ($jornada) {
                    $minutosMuertos = (int) ($resumenService->resumenDia($jornada)['minutos_parado'] ?? 0);
                } else {
                    $fin = $sesion->hora_salida ?? now(config('app.timezone'));
                    $minutosMuertos = max(
                        0,
                        (int) $sesion->hora_entrada->diffInMinutes($fin)
                        - (int) ($sesion->minutos_pausa ?? 0)
                        - (int) ($sesion->minutos_almuerzo ?? 0)
                    );
                }

                $tiempoMuertoPorUsuario[$sesion->user_id] =
                    ($tiempoMuertoPorUsuario[$sesion->user_id] ?? 0) + ($minutosMuertos * 60);
            }
        }

        foreach ($resultado as &$item) {
            $segundos       = $item['tiempo_total_segundos'];
            $horas          = $segundos > 0 ? $segundos / 3600 : 0;
            $bolsasPorHora  = $segundos > 0 ? ($item['produccion_total'] * 3600) / $segundos : 0;
            $eficiencia     = $metaHora > 0 ? ($bolsasPorHora / $metaHora) * 100 : 0;

            $item['horas']               = round($horas, 2);
            $item['tiempo_total_sesion_segundos'] = $segundos + $item['tiempo_pausa_segundos'];
            $item['horas_pausa'] = round($item['tiempo_pausa_segundos'] / 3600, 2);
            $item['motivos_pausa'] = collect($item['motivos_pausa'])
                ->map(fn ($segundos, $motivo) => [
                    'motivo' => $motivo,
                    'segundos' => $segundos,
                    'horas' => round($segundos / 3600, 2),
                    'clasificacion' => $this->esApoyoOperativo($motivo) ? 'APOYO_OPERATIVO' : 'TIEMPO_DETENIDO',
                ])
                ->sortByDesc('segundos')
                ->values()
                ->all();
            $item['tiempo_apoyo_operativo_segundos'] += (int) collect($item['motivos_pausa'])
                ->where('clasificacion', 'APOYO_OPERATIVO')
                ->sum('segundos');
            $item['tiempo_muerto_segundos'] = max(
                0,
                (int) ($tiempoMuertoPorUsuario[$item['usuario_id']] ?? 0)
                - $item['tiempo_apoyo_operativo_segundos']
            );
            $item['horas_tiempo_muerto'] = round($item['tiempo_muerto_segundos'] / 3600, 2);
            $item['bolsas_por_hora']     = round($bolsasPorHora, 2);
            $item['eficiencia_porcentaje'] = round($eficiencia, 2);
            $item['estado'] = $item['produccion_total'] <= 0 && $item['tiempo_apoyo_operativo_segundos'] > 0
                ? 'OPERATIVO'
                : $this->clasificarEstado($eficiencia);
        }

        // Excluir usuarios sin producción significativa (menos de 10 minutos)
        $resultado = array_filter($resultado, fn($i) =>
            ($i['produccion_total'] > 0 && $i['tiempo_total_segundos'] > 600)
            || $i['tiempo_apoyo_operativo_segundos'] > 600
        );

        return array_values($resultado);
    }

    private function esApoyoOperativo(string $motivo): bool
    {
        return in_array($motivo, [
            'Alistamiento de material',
            'Aseo y organización',
            'Recepción de material',
            'Conteo de inventario',
            'Cargue y descargue',
            'Apoyo en otro alistamiento',
            'Apoyo en otra orden de trabajo',
        ], true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RENDIMIENTO POR PERÍODO (diario / semanal / mensual)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Agrupa la producción por período y calcula rendimiento vs meta del período.
     *
     * Lógica:
     *   meta_hora       = meta_unidades_hora (configuración)
     *   horas_diarias   = horas_semanales / 5
     *   meta_diaria     = meta_hora × horas_diarias
     *   meta_semanal    = meta_hora × horas_semanales
     *   meta_mensual    = meta_hora × horas_semanales × (52/12)
     *   rendimiento (%) = (produccion_período / meta_período) × 100
     */
    public function obtenerRendimientoPorPeriodo(array $filtros): array
    {
        [$sedeId, $fechaInicio, $fechaFin] = $this->resolverFiltrosBase($filtros);
        $tipoPeriodo = $filtros['tipo_periodo'] ?? 'diario';

        $metas = VsmConfiguracion::metas();

        $alistamientos = $this->queryAlistamientos($sedeId, $fechaInicio, $fechaFin)->get();

        // Fix N+1
        $produccionMap = $this->cargarProduccionMap($alistamientos->pluck('id')->all());

        $periodos = [];

        foreach ($alistamientos as $alist) {
            $fecha = Carbon::parse($alist->fecha);

            [$key, $label, $metaPeriodo] = $this->infoPeriodo($fecha, $tipoPeriodo, $metas);

            if (!isset($periodos[$key])) {
                $periodos[$key] = [
                    'key'      => $key,
                    'label'    => $label,
                    'meta'     => round($metaPeriodo),
                    'usuarios' => [],
                ];
            }

            foreach ($alist->usuarios as $usuario) {
                if ($sedeId && $usuario->sede_id != $sedeId) continue;

                $userId     = $usuario->id;
                $produccion = $produccionMap["{$alist->id}_{$userId}"] ?? 0;

                if ($produccion <= 0) continue;

                if (!isset($periodos[$key]['usuarios'][$userId])) {
                    $horasSemanales = (float) ($usuario->pivot->horas_semanales_snapshot ?? $metas['horas_semanales']);
                    $periodos[$key]['usuarios'][$userId] = [
                        'usuario_id' => $userId,
                        'nombre'     => $usuario->name,
                        'produccion' => 0,
                        'horas_semanales' => $horasSemanales,
                        'origen_jornada' => $usuario->pivot->horas_semanales_snapshot !== null ? 'NOMINA' : 'VSM',
                    ];
                }

                $periodos[$key]['usuarios'][$userId]['produccion'] += $produccion;
            }
        }

        // Ordenar cronológicamente y calcular rendimiento
        ksort($periodos);

        foreach ($periodos as &$periodo) {
            $meta = $periodo['meta'];

            $periodo['usuarios'] = array_values(array_map(function (array $u) use ($meta, $tipoPeriodo, $metas) {
                $metaUsuario = $this->metaPorJornada(
                    $tipoPeriodo,
                    (float) $u['horas_semanales'],
                    (float) $metas['meta_hora']
                );
                $metaAplicada = $metaUsuario > 0 ? $metaUsuario : $meta;
                $rendimiento = $metaAplicada > 0 ? ($u['produccion'] / $metaAplicada) * 100 : 0;

                return [
                    'usuario_id'  => $u['usuario_id'],
                    'nombre'      => $u['nombre'],
                    'produccion'  => $u['produccion'],
                    'meta'        => round($metaAplicada, 2),
                    'horas_semanales_jornada' => $u['horas_semanales'],
                    'origen_jornada' => $u['origen_jornada'],
                    'rendimiento' => round($rendimiento, 2),
                    'estado'      => $this->clasificarEstado($rendimiento),
                ];
            }, $periodo['usuarios']));
        }

        // Quitar períodos sin datos
        $periodos = array_values(array_filter($periodos, fn($p) => !empty($p['usuarios'])));

        return [
            'periodos'        => $periodos,
            'tipo_periodo'    => $tipoPeriodo,
            'meta_hora'       => $metas['meta_hora'],
            'horas_diarias'   => $metas['horas_diarias'],
            'horas_semanales' => $metas['horas_semanales'],
            'meta_diaria'     => $metas['meta_diaria'],
            'meta_semanal'    => $metas['meta_semanal'],
            'meta_mensual'    => $metas['meta_mensual'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────

    private function resolverFiltrosBase(array $filtros): array
    {
        $user        = request()->user();
        $sedeId      = $filtros['sede_id']      ?? $user?->sede_id;
        $fechaInicio = $filtros['fecha_inicio'] ?? now()->startOfMonth()->toDateString();
        $fechaFin    = $filtros['fecha_fin']    ?? now()->toDateString();

        return [$sedeId, $fechaInicio, $fechaFin];
    }

    private function queryAlistamientos(?int $sedeId, string $fechaInicio, string $fechaFin)
    {
        $query = Alistamiento::with(['usuarios', 'tiempos'])
            ->where('estado', 'FINALIZADO')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($sedeId) {
            $query->where(function ($q) use ($sedeId) {
                $q->where('sede_id', $sedeId)
                    ->orWhereHas('ordenTrabajo.ordenCompra', function ($ordenQuery) use ($sedeId) {
                        $ordenQuery->where('sede_id', $sedeId);
                    });
            });
        }

        return $query;
    }

    /**
     * Carga toda la producción de los alistamientos dados en UNA sola query.
     * Retorna un mapa: "{alistamiento_id}_{usuario_id}" => cantidad_alistada
     */
    private function cargarProduccionMap(array $alistamientoIds): array
    {
        if (empty($alistamientoIds)) return [];

        return AlistamientoUsuarioDetalle::whereIn('alistamiento_id', $alistamientoIds)
            ->get()
            ->groupBy(fn($d) => $d->alistamiento_id . '_' . $d->usuario_id)
            ->map(fn($grupo) => $grupo->sum('cantidad_alistada'))
            ->all();
    }

    /**
     * Calcula pausas atribuibles a cada usuario emparejando PAUSA con
     * REANUDACION. Los eventos históricos sin user_id no se asignan para
     * evitar imputar una pausa a la persona equivocada.
     */
    private function calcularPausasPorUsuario(Alistamiento $alist): array
    {
        $resultado = [];

        $eventosPorUsuario = $alist->tiempos
            ->filter(fn ($evento) => $evento->user_id !== null)
            ->sortBy('fecha_hora')
            ->groupBy('user_id');

        foreach ($eventosPorUsuario as $userId => $eventos) {
            $pausaAbierta = null;
            $motivo = 'Sin motivo registrado';
            $segundos = 0;
            $motivos = [];

            foreach ($eventos as $evento) {
                if (in_array($evento->tipo, ['PAUSA', 'PAUSA_USUARIO'], true)) {
                    if ($pausaAbierta === null) {
                        $pausaAbierta = Carbon::parse($evento->fecha_hora);
                        $motivo = trim((string) $evento->razon) ?: 'Sin motivo registrado';
                    }
                    continue;
                }

                if ($evento->tipo === 'REANUDACION' && $pausaAbierta !== null) {
                    $duracion = max(0, $pausaAbierta->diffInSeconds(Carbon::parse($evento->fecha_hora)));
                    $segundos += $duracion;
                    $motivos[$motivo] = ($motivos[$motivo] ?? 0) + $duracion;
                    $pausaAbierta = null;
                }
            }

            if ($pausaAbierta !== null) {
                $fin = $alist->updated_at ? Carbon::parse($alist->updated_at) : now();
                $duracion = max(0, $pausaAbierta->diffInSeconds($fin));
                $segundos += $duracion;
                $motivos[$motivo] = ($motivos[$motivo] ?? 0) + $duracion;
            }

            $resultado[(int) $userId] = compact('segundos', 'motivos');
        }

        return $resultado;
    }

    /**
     * Retorna [key, label, meta_del_período] según el tipo de agrupación.
     */
    private function infoPeriodo(Carbon $fecha, string $tipo, array $metas): array
    {
        $mesesEs = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        return match ($tipo) {
            'semanal' => [
                $fecha->format('o-W'),  // ISO year-week (evita ambigüedad dic/ene)
                'Sem ' . $fecha->isoWeek() . ' (' . $fecha->copy()->startOfWeek()->format('d M') . '-' . $fecha->copy()->endOfWeek()->format('d M') . ')',
                $metas['meta_semanal'],
            ],
            'mensual' => [
                $fecha->format('Y-m'),
                $mesesEs[$fecha->month] . ' ' . $fecha->year,
                $metas['meta_mensual'],
            ],
            default => [  // diario
                $fecha->format('Y-m-d'),
                $fecha->format('d ') . $mesesEs[$fecha->month],
                $metas['meta_diaria'],
            ],
        };
    }

    private function metaPorJornada(string $tipoPeriodo, float $horasSemanales, float $metaHora): float
    {
        return match ($tipoPeriodo) {
            'semanal' => $metaHora * $horasSemanales,
            'mensual' => $metaHora * $horasSemanales * (52 / 12),
            default => $metaHora * ($horasSemanales / 5),
        };
    }

    private function clasificarEstado(float $rendimiento): string
    {
        if ($rendimiento >= 100) return 'EFICIENTE';
        if ($rendimiento >= 80)  return 'NORMAL';
        return 'BAJO';
    }
}
