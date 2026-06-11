<?php

namespace App\Services\Vsm;

use App\Models\Vsm\Alistamiento;
use App\Models\Vsm\AlistamientoUsuarioDetalle;
use App\Models\Vsm\VsmConfiguracion;
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
            foreach ($alist->usuarios as $usuario) {
                if ($sedeId && $usuario->sede_id != $sedeId) continue;

                $userId     = $usuario->id;
                $produccion = $produccionMap["{$alist->id}_{$userId}"] ?? 0;

                if (!isset($resultado[$userId])) {
                    $resultado[$userId] = [
                        'usuario_id'            => $userId,
                        'nombre'                => $usuario->name,
                        'produccion_total'      => 0,
                        'tiempo_total_segundos' => 0,
                    ];
                }

                $resultado[$userId]['produccion_total']      += $produccion;
                $resultado[$userId]['tiempo_total_segundos'] += max(0, (int) $usuario->pivot->tiempo_segundos);
            }
        }

        $metaHora = VsmConfiguracion::metaVigente(705.88);

        foreach ($resultado as &$item) {
            $segundos       = $item['tiempo_total_segundos'];
            $horas          = $segundos > 0 ? $segundos / 3600 : 0;
            $bolsasPorHora  = $segundos > 0 ? ($item['produccion_total'] * 3600) / $segundos : 0;
            $eficiencia     = $metaHora > 0 ? ($bolsasPorHora / $metaHora) * 100 : 0;

            $item['horas']               = round($horas, 2);
            $item['bolsas_por_hora']     = round($bolsasPorHora, 2);
            $item['eficiencia_porcentaje'] = round($eficiencia, 2);
            $item['estado']              = $this->clasificarEstado($eficiencia);
        }

        // Excluir usuarios sin producción significativa (menos de 10 minutos)
        $resultado = array_filter($resultado, fn($i) =>
            $i['produccion_total'] > 0 && $i['tiempo_total_segundos'] > 600
        );

        return array_values($resultado);
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
                    $periodos[$key]['usuarios'][$userId] = [
                        'usuario_id' => $userId,
                        'nombre'     => $usuario->name,
                        'produccion' => 0,
                    ];
                }

                $periodos[$key]['usuarios'][$userId]['produccion'] += $produccion;
            }
        }

        // Ordenar cronológicamente y calcular rendimiento
        ksort($periodos);

        foreach ($periodos as &$periodo) {
            $meta = $periodo['meta'];

            $periodo['usuarios'] = array_values(array_map(function (array $u) use ($meta) {
                $rendimiento = $meta > 0 ? ($u['produccion'] / $meta) * 100 : 0;

                return [
                    'usuario_id'  => $u['usuario_id'],
                    'nombre'      => $u['nombre'],
                    'produccion'  => $u['produccion'],
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
        $query = Alistamiento::with(['usuarios'])
            ->where('estado', 'FINALIZADO')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($sedeId) {
            $query->whereHas('ordenTrabajo.ordenCompra', function ($q) use ($sedeId) {
                $q->where('sede_id', $sedeId);
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

    private function clasificarEstado(float $rendimiento): string
    {
        if ($rendimiento >= 100) return 'EFICIENTE';
        if ($rendimiento >= 80)  return 'NORMAL';
        return 'BAJO';
    }
}
