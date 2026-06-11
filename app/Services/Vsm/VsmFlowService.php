<?php

namespace App\Services\Vsm;

use App\Models\Crm\OrdenDeTrabajo;
use App\RolEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class VsmFlowService
{
    public function obtenerFlujo($user, array $filtros = []): array
    {
        $sedeId = $this->resolverSede($user, $filtros['sede_id'] ?? null);
        $fechaInicio = $filtros['fecha_inicio'] ?? now()->startOfMonth()->toDateString();
        $fechaFin = $filtros['fecha_fin'] ?? now()->toDateString();
        $umbralDetenidoHoras = min(720, max(1, (int) ($filtros['umbral_horas'] ?? 24)));

        $ordenes = OrdenDeTrabajo::with([
            'cliente:id,nombre',
            'ordenCompra:id,cliente_id,sede_id,fecha_despacho,created_at',
            'ordenCompra.deliveryEvents.records',
            'movimientosStock:id,orden_trabajo_id,tipo,anulado,created_at,updated_at',
            'alistamientos.tiempos:id,alistamiento_id,tipo,fecha_hora,user_id',
        ])
            ->whereHas('ordenCompra', function (Builder $query) use ($sedeId, $fechaInicio, $fechaFin) {
                $query->whereBetween('created_at', [
                    Carbon::parse($fechaInicio)->startOfDay(),
                    Carbon::parse($fechaFin)->endOfDay(),
                ]);

                if ($sedeId) {
                    $query->where('sede_id', $sedeId);
                }
            })
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $etapas = collect([
            'pendientes' => $this->etapa('OT pendientes', 'pendientes'),
            'inventario' => $this->etapa('Preparación inventario', 'inventario'),
            'alistando' => $this->etapa('Alistando', 'alistando'),
            'finalizadas' => $this->etapa('Listas para despacho', 'finalizadas'),
            'delivery' => $this->etapa('En ruta', 'delivery'),
            'entregadas' => $this->etapa('Entregadas', 'entregadas'),
        ]);

        $leadTimes = [];
        $tiemposValor = [];
        $procesos = $this->procesos();
        $ordenesDetenidas = [];

        foreach ($ordenes as $orden) {
            $item = $this->transformarOrden($orden);
            $etapas[$item['etapa']]['items'][] = $item;
            $etapas[$item['etapa']]['tiempos'][] = $item['tiempo_etapa_segundos'];

            if ($item['lead_time_segundos'] !== null) {
                $leadTimes[] = $item['lead_time_segundos'];
                $tiemposValor[] = $item['tiempo_valor_agregado_segundos'];
            }

            foreach ($item['tiempos_proceso'] as $codigo => $segundos) {
                if ($segundos !== null) {
                    $procesos[$codigo]['muestras'][] = $segundos;
                }
            }

            if ($item['etapa'] !== 'entregadas' && $item['tiempo_etapa_segundos'] >= ($umbralDetenidoHoras * 3600)) {
                $ordenesDetenidas[] = [
                    'orden_trabajo_id' => $item['orden_trabajo_id'],
                    'orden_compra_id' => $item['orden_compra_id'],
                    'cliente' => $item['cliente']['nombre'],
                    'etapa' => $item['etapa'],
                    'tiempo_detenido_segundos' => $item['tiempo_etapa_segundos'],
                    'tiempo_detenido_horas' => $item['tiempo_etapa_horas'],
                ];
            }
        }

        $etapas = $etapas->map(function (array $etapa) {
            $tiempos = $etapa['tiempos'];
            unset($etapa['tiempos']);

            $etapa['cantidad'] = count($etapa['items']);
            $etapa['tiempo_promedio_segundos'] = $this->promedio($tiempos);
            $etapa['tiempo_promedio_horas'] = round($etapa['tiempo_promedio_segundos'] / 3600, 2);

            return $etapa;
        });

        $leadTimePromedio = $this->promedio($leadTimes);
        $tiempoVaPromedio = $this->promedio($tiemposValor);
        $pce = $leadTimePromedio > 0 ? ($tiempoVaPromedio / $leadTimePromedio) * 100 : 0;
        $procesos = collect($procesos)->map(function (array $proceso) {
            $muestras = $proceso['muestras'];
            unset($proceso['muestras']);
            $proceso['muestras_total'] = count($muestras);
            $proceso['tiempo_promedio_segundos'] = $this->promedio($muestras);
            $proceso['tiempo_promedio_horas'] = round($proceso['tiempo_promedio_segundos'] / 3600, 2);

            return $proceso;
        });
        $cuelloBotella = $procesos->sortByDesc('tiempo_promedio_segundos')->first();
        $ordenesDetenidas = collect($ordenesDetenidas)
            ->sortByDesc('tiempo_detenido_segundos')
            ->values();
        $totalOrdenesDetenidas = $ordenesDetenidas->count();
        $ordenesDetenidas = $ordenesDetenidas->take(50)->values();

        return [
            'resumen' => [
                'total_ordenes' => $ordenes->count(),
                'en_proceso' => $etapas->except('entregadas')->sum('cantidad'),
                'entregadas' => $etapas['entregadas']['cantidad'],
                'lead_time_promedio_segundos' => $leadTimePromedio,
                'lead_time_promedio_horas' => round($leadTimePromedio / 3600, 2),
                'tiempo_valor_agregado_promedio_segundos' => $tiempoVaPromedio,
                'tiempo_no_valor_agregado_promedio_segundos' => max(0, $leadTimePromedio - $tiempoVaPromedio),
                'tiempo_no_valor_agregado_promedio_horas' => round(max(0, $leadTimePromedio - $tiempoVaPromedio) / 3600, 2),
                'pce_porcentaje' => round($pce, 2),
                'ordenes_detenidas' => $totalOrdenesDetenidas,
                'umbral_detenido_horas' => $umbralDetenidoHoras,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'sede_id' => $sedeId,
            ],
            'etapas' => $etapas->values()->all(),
            'analisis' => [
                'procesos' => $procesos->values()->all(),
                'cuello_botella' => $cuelloBotella,
                'ordenes_detenidas' => $ordenesDetenidas->all(),
            ],

            // Compatibilidad temporal con consumidores existentes.
            'pendientes' => $etapas['pendientes']['items'],
            'inventario' => $etapas['inventario']['items'],
            'alistando' => $etapas['alistando']['items'],
            'finalizadas' => $etapas['finalizadas']['items'],
            'delivery' => $etapas['delivery']['items'],
            'entregadas' => $etapas['entregadas']['items'],
        ];
    }

    private function transformarOrden(OrdenDeTrabajo $orden): array
    {
        $ordenCompra = $orden->ordenCompra;
        $alistamientos = $orden->alistamientos;
        $alistamientoActivo = $alistamientos->first(
            fn ($item) => in_array($item->estado, ['INICIADO', 'REANUDADO', 'PAUSADO'])
        );
        $alistamientoFinalizado = $alistamientos
            ->where('estado', 'FINALIZADO')
            ->sortByDesc('updated_at')
            ->first();

        $deliveryEvents = $ordenCompra?->deliveryEvents ?? collect();
        $deliveryActivo = $deliveryEvents
            ->whereIn('estado', ['pendiente', 'en_ruta'])
            ->sortByDesc('updated_at')
            ->first();
        $deliveryCompletado = $deliveryEvents
            ->where('estado', 'completado')
            ->sortByDesc('updated_at')
            ->first();

        $registroEntrega = $deliveryEvents
            ->flatMap->records
            ->where('resultado', 'entregado')
            ->sortByDesc('fecha_real')
            ->first();

        $inicioPedido = $ordenCompra?->created_at;
        $inicioOt = $orden->created_at;
        $primerMovimiento = $orden->movimientosStock
            ->where('anulado', false)
            ->sortBy('created_at')
            ->first();
        $ultimoMovimiento = $orden->movimientosStock
            ->where('anulado', false)
            ->sortByDesc('created_at')
            ->first();
        $inicioAlistamiento = $alistamientos
            ->flatMap->tiempos
            ->where('tipo', 'INICIO')
            ->sortBy('fecha_hora')
            ->first()?->fecha_hora;
        $finAlistamiento = $alistamientoFinalizado?->tiempos
            ->where('tipo', 'FINALIZACION')
            ->sortByDesc('fecha_hora')
            ->first()?->fecha_hora ?? $alistamientoFinalizado?->updated_at;
        $inicioRuta = $deliveryActivo?->created_at ?? $deliveryCompletado?->created_at;
        $finEntrega = $this->fechaEntregaReal($registroEntrega)
            ?? $deliveryCompletado?->updated_at;

        [$etapa, $inicioEtapa] = match (true) {
            (bool) ($registroEntrega || $deliveryCompletado) => [
                'entregadas',
                $inicioRuta ?? $finAlistamiento ?? $inicioPedido,
            ],
            (bool) $deliveryActivo => ['delivery', $inicioRuta],
            (bool) $alistamientoFinalizado => ['finalizadas', $finAlistamiento],
            (bool) $alistamientoActivo => ['alistando', $inicioAlistamiento ?? $alistamientoActivo->created_at],
            (bool) $primerMovimiento => ['inventario', $primerMovimiento->created_at],
            default => ['pendientes', $inicioOt],
        };

        $finEtapa = $etapa === 'entregadas' ? $finEntrega : now();
        $tiempoEtapa = $this->diferenciaSegundos($inicioEtapa, $finEtapa);
        $leadTime = $finEntrega ? $this->diferenciaSegundos($inicioPedido, $finEntrega) : null;
        $tiempoVa = max(0, (int) ($alistamientoFinalizado?->duracion_segundos ?? 0));
        $tiemposProceso = [
            'espera_creacion_ot' => $this->diferenciaSegundosNullable($inicioPedido, $inicioOt),
            'espera_inicio_inventario' => $this->diferenciaSegundosNullable($inicioOt, $primerMovimiento?->created_at),
            'procesamiento_inventario' => $this->diferenciaSegundosNullable(
                $primerMovimiento?->created_at,
                $ultimoMovimiento?->created_at
            ),
            'espera_inicio_alistamiento' => $this->diferenciaSegundosNullable(
                $ultimoMovimiento?->created_at ?? $primerMovimiento?->created_at,
                $inicioAlistamiento
            ),
            'alistamiento_productivo' => $alistamientoFinalizado ? $tiempoVa : null,
            'espera_despacho' => $this->diferenciaSegundosNullable($finAlistamiento, $inicioRuta),
            'transporte_entrega' => $this->diferenciaSegundosNullable($inicioRuta, $finEntrega),
        ];

        return [
            'id' => $orden->id,
            'orden_trabajo_id' => $orden->id,
            'orden_compra_id' => $orden->orden_compra_id,
            'cliente' => [
                'id' => $orden->cliente_id,
                'nombre' => $orden->cliente?->nombre ?? 'Sin cliente',
            ],
            'etapa' => $etapa,
            'estado_id' => $orden->estado_id,
            'tiempo_etapa_segundos' => $tiempoEtapa,
            'tiempo_etapa_horas' => round($tiempoEtapa / 3600, 2),
            'lead_time_segundos' => $leadTime,
            'tiempo_valor_agregado_segundos' => $tiempoVa,
            'tiempo_no_valor_agregado_segundos' => $leadTime !== null ? max(0, $leadTime - $tiempoVa) : null,
            'tiempos_proceso' => $tiemposProceso,
            'hitos' => [
                'pedido_creado' => $inicioPedido,
                'ot_creada' => $inicioOt,
                'primer_movimiento_inventario' => $primerMovimiento?->created_at,
                'ultimo_movimiento_inventario' => $ultimoMovimiento?->created_at,
                'alistamiento_iniciado' => $inicioAlistamiento,
                'alistamiento_finalizado' => $finAlistamiento,
                'fecha_despacho' => $ordenCompra?->fecha_despacho,
                'ruta_creada' => $inicioRuta,
                'entrega_real' => $finEntrega,
            ],
        ];
    }

    private function etapa(string $nombre, string $codigo): array
    {
        return [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'items' => [],
            'tiempos' => [],
        ];
    }

    private function procesos(): array
    {
        return [
            'espera_creacion_ot' => $this->proceso('Espera para crear OT', 'espera_creacion_ot', 'espera'),
            'espera_inicio_inventario' => $this->proceso('Espera para inventario', 'espera_inicio_inventario', 'espera'),
            'procesamiento_inventario' => $this->proceso('Procesamiento de inventario', 'procesamiento_inventario', 'proceso'),
            'espera_inicio_alistamiento' => $this->proceso('Espera para alistamiento', 'espera_inicio_alistamiento', 'espera'),
            'alistamiento_productivo' => $this->proceso('Alistamiento productivo', 'alistamiento_productivo', 'valor_agregado'),
            'espera_despacho' => $this->proceso('Espera para despacho', 'espera_despacho', 'espera'),
            'transporte_entrega' => $this->proceso('Transporte hasta entrega', 'transporte_entrega', 'transporte'),
        ];
    }

    private function proceso(string $nombre, string $codigo, string $tipo): array
    {
        return [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'tipo' => $tipo,
            'muestras' => [],
        ];
    }

    private function resolverSede($user, $sedeIdFiltro): ?int
    {
        $puedeVerTodas = in_array((int) $user->role_id, [
            RolEnum::ADMINISTRADOR->value,
            RolEnum::ADMINISTRATIVO->value,
        ]);

        return ($puedeVerTodas && $sedeIdFiltro)
            ? (int) $sedeIdFiltro
            : $user->sede_id;
    }

    private function fechaEntregaReal($registro): ?Carbon
    {
        if (!$registro?->fecha_real) {
            return null;
        }

        $fecha = Carbon::parse($registro->fecha_real);
        $hora = $registro->getRawOriginal('hora_real');

        return $hora
            ? Carbon::parse($fecha->format('Y-m-d') . ' ' . $hora)
            : $fecha->endOfDay();
    }

    private function diferenciaSegundos($inicio, $fin): int
    {
        if (!$inicio || !$fin) {
            return 0;
        }

        $inicio = Carbon::parse($inicio);
        $fin = Carbon::parse($fin);

        return $fin->lessThan($inicio) ? 0 : $inicio->diffInSeconds($fin);
    }

    private function diferenciaSegundosNullable($inicio, $fin): ?int
    {
        return ($inicio && $fin) ? $this->diferenciaSegundos($inicio, $fin) : null;
    }

    private function promedio(array $valores): int
    {
        return count($valores) > 0
            ? (int) round(array_sum($valores) / count($valores))
            : 0;
    }
}
