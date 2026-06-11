<?php

namespace App\Services\Vsm;

use App\EstadoEnum;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Vsm\AlistamientoDetalle;
use App\RolEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class VsmCapacityService
{
    private const HORAS_JORNADA_TEMPORAL = 9;

    public function calcular($user, array $filtros = []): array
    {
        $usuarios = min(100, max(1, (int) ($filtros['usuarios'] ?? 1)));
        $diasObjetivo = min(30, max(1, (int) ($filtros['dias_objetivo'] ?? 5)));
        $segundosJornada = self::HORAS_JORNADA_TEMPORAL * 3600;
        $sedeId = $this->resolverSede($user, $filtros['sede_id'] ?? null);

        $ordenes = OrdenDeTrabajo::with([
            'ordenCompra:id,cliente_id,fecha_entrega',
            'ordenCompra.cliente:id,nombre',
            'ordenCompra.detalles:id,orden_compra_id,product_id,cantidad,cantidad_enviada,faltantes',
        ])
            ->whereIn('estado_id', [
                EstadoEnum::PENDIENTE->value,
                EstadoEnum::ENTREGA_PARCIAL->value,
            ])
            ->whereHas('ordenCompra', function (Builder $query) use ($sedeId) {
                if ($sedeId) {
                    $query->where('sede_id', $sedeId);
                }
            })
            ->get()
            ->sortBy(fn ($orden) => $orden->fecha_entrega ?? $orden->ordenCompra?->fecha_entrega ?? '9999-12-31')
            ->values();

        $productIds = $ordenes
            ->flatMap(fn ($orden) => $orden->ordenCompra?->detalles?->pluck('product_id') ?? collect())
            ->filter()
            ->unique()
            ->values();

        $tpuPorProducto = AlistamientoDetalle::query()
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->selectRaw('
                product_id,
                SUM(CASE WHEN cantidad_alistada > 0 THEN cantidad_alistada ELSE cantidad_programada END) AS total_unidades,
                SUM(tiempo_parcial_segundos) AS total_segundos
            ')
            ->get()
            ->mapWithKeys(function ($item) {
                $unidades = (float) $item->total_unidades;

                return [
                    $item->product_id => $unidades > 0 && $item->total_segundos > 0
                        ? (float) $item->total_segundos / $unidades
                        : null,
                ];
            });

        $capacidadDiariaSegundos = $usuarios * $segundosJornada;
        $capacidadHorizonteSegundos = $capacidadDiariaSegundos * $diasObjetivo;
        $tiempoProcesoDisponibleSegundos = $segundosJornada * $diasObjetivo;
        $cargaAcumuladaSegundos = 0;
        $demandaUnidades = 0;
        $unidadesConHistorico = 0;
        $ordenesRiesgo = 0;
        $ordenesSinHistoricoCompleto = 0;

        $detalleOrdenes = $ordenes->map(function ($orden) use (
            $tpuPorProducto,
            $capacidadDiariaSegundos,
            &$cargaAcumuladaSegundos,
            &$demandaUnidades,
            &$unidadesConHistorico,
            &$ordenesRiesgo,
            &$ordenesSinHistoricoCompleto
        ) {
            $cargaOrden = 0;
            $unidadesOrden = 0;
            $unidadesEstimadas = 0;
            $productosSinHistorico = 0;

            foreach ($orden->ordenCompra?->detalles ?? [] as $detalle) {
                $faltantes = max(
                    0,
                    (int) ($detalle->faltantes ?? ((int) $detalle->cantidad - (int) $detalle->cantidad_enviada))
                );
                $unidadesOrden += $faltantes;
                $tpu = $tpuPorProducto->get($detalle->product_id);

                if ($faltantes > 0 && $tpu) {
                    $cargaOrden += $faltantes * $tpu;
                    $unidadesEstimadas += $faltantes;
                } elseif ($faltantes > 0) {
                    $productosSinHistorico++;
                }
            }

            $demandaUnidades += $unidadesOrden;
            $unidadesConHistorico += $unidadesEstimadas;
            $cargaAcumuladaSegundos += $cargaOrden;
            $diasAcumulados = $capacidadDiariaSegundos > 0
                ? (int) ceil($cargaAcumuladaSegundos / $capacidadDiariaSegundos)
                : 0;
            $fechaEstimada = $this->sumarDiasLaborales(now(), max(0, $diasAcumulados - 1));
            $fechaPrometida = $orden->fecha_entrega ?? $orden->ordenCompra?->fecha_entrega;
            $historicoCompleto = $unidadesOrden === $unidadesEstimadas;
            $enRiesgo = $fechaPrometida && $historicoCompleto
                ? $fechaEstimada->endOfDay()->greaterThan(Carbon::parse($fechaPrometida)->endOfDay())
                : null;

            if ($enRiesgo === true) {
                $ordenesRiesgo++;
            }
            if (!$historicoCompleto) {
                $ordenesSinHistoricoCompleto++;
            }

            return [
                'orden_trabajo_id' => $orden->id,
                'cliente' => $orden->ordenCompra?->cliente?->nombre ?? 'Sin cliente',
                'fecha_prometida' => $fechaPrometida,
                'fecha_estimada' => $fechaEstimada->toDateString(),
                'unidades_pendientes' => $unidadesOrden,
                'horas_estimadas' => round($cargaOrden / 3600, 2),
                'cobertura_historica_porcentaje' => $unidadesOrden > 0
                    ? round(($unidadesEstimadas / $unidadesOrden) * 100, 2)
                    : 100,
                'productos_sin_historico' => $productosSinHistorico,
                'historico_completo' => $historicoCompleto,
                'en_riesgo' => $enRiesgo,
            ];
        });

        $cargaTotalSegundos = $cargaAcumuladaSegundos;
        $cycleTime = $unidadesConHistorico > 0 ? $cargaTotalSegundos / $unidadesConHistorico : 0;
        $taktTime = $demandaUnidades > 0 ? $tiempoProcesoDisponibleSegundos / $demandaUnidades : 0;
        $usuariosRequeridos = $capacidadHorizonteSegundos > 0
            ? (int) ceil($cargaTotalSegundos / ($segundosJornada * $diasObjetivo))
            : 0;
        $capacidadUnidades = $cycleTime > 0 ? $capacidadHorizonteSegundos / $cycleTime : 0;

        return [
            'resumen' => [
                'jornada_horas' => self::HORAS_JORNADA_TEMPORAL,
                'jornada_origen' => 'SUPUESTO_TEMPORAL_HASTA_INTEGRACION_NOMINA',
                'dias_objetivo' => $diasObjetivo,
                'usuarios_disponibles' => $usuarios,
                'usuarios_requeridos' => $usuariosRequeridos,
                'brecha_usuarios' => $usuarios - $usuariosRequeridos,
                'demanda_pendiente_unidades' => $demandaUnidades,
                'carga_pendiente_horas' => round($cargaTotalSegundos / 3600, 2),
                'capacidad_disponible_horas' => round($capacidadHorizonteSegundos / 3600, 2),
                'capacidad_estimada_unidades' => round($capacidadUnidades),
                'takt_segundos_por_unidad' => round($taktTime, 2),
                'cycle_time_segundos_por_unidad' => round($cycleTime, 2),
                'cumple_takt' => $cycleTime > 0 && $taktTime > 0 ? $cycleTime <= $taktTime : null,
                'ordenes_en_riesgo' => $ordenesRiesgo,
                'ordenes_sin_historico_completo' => $ordenesSinHistoricoCompleto,
                'cobertura_historica_porcentaje' => $demandaUnidades > 0
                    ? round(($unidadesConHistorico / $demandaUnidades) * 100, 2)
                    : 100,
                'sede_id' => $sedeId,
            ],
            'ordenes' => $detalleOrdenes->all(),
            'advertencias' => [
                'La jornada de 9 horas es temporal hasta integrar horarios reales desde nómina.',
                'Las fechas estimadas consideran lunes a viernes y no descuentan festivos.',
                'Las referencias sin histórico de alistamiento no aportan carga estimada.',
            ],
        ];
    }

    private function sumarDiasLaborales(Carbon $fecha, int $dias): Carbon
    {
        $resultado = $fecha->copy()->startOfDay();

        while ($dias > 0) {
            $resultado->addDay();
            if ($resultado->isWeekday()) {
                $dias--;
            }
        }

        while (!$resultado->isWeekday()) {
            $resultado->addDay();
        }

        return $resultado;
    }

    private function resolverSede($user, $sedeIdFiltro): ?int
    {
        $puedeVerTodas = in_array((int) $user->role_id, [
            RolEnum::ADMINISTRADOR->value,
            RolEnum::ADMINISTRATIVO->value,
        ]);

        return ($puedeVerTodas && $sedeIdFiltro) ? (int) $sedeIdFiltro : $user->sede_id;
    }
}
