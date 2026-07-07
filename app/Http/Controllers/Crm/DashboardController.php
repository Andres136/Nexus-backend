<?php

namespace App\Http\Controllers\Crm;

use App\EstadoEnum;
use App\Exports\OrdenesCriticasExport;
use App\Http\Controllers\Controller;
use App\Models\Crm\Cliente;
use App\Models\Crm\Inventario;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenDeTrabajo;
use App\Services\Crm\KpiService;
use App\Services\OrdenCompraService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{

protected $kpiService;


public function __construct(KpiService $kpiService)
{
    $this->kpiService = $kpiService;
}


public function kpis(Request $request)
{


    $year = $request->get('year');

    return response()->json(
        $this->kpiService->getDashboardKpisYearly($year)
    );
}
    public function getDashboardData()
{
    //  Carga optimizada (menos columnas)
    $ordenes = Orden_Compra::with([
        'ordenTrabajo:id,orden_compra_id,updated_at',
        'detalles:orden_compra_id,faltantes,cantidad_enviada',
        'cliente:id,nombre',
        'creador:id,name'
    ])
    ->select('id', 'cliente_id', 'user_id', 'fecha_entrega', 'estado_id')
    ->whereNot('estado_id', EstadoEnum::INACTIVO->value)
    ->get();

    //  INDEXACIÓN (CLAVE DE PERFORMANCE)
    $ordenesIndexadas = $ordenes->keyBy('id');

    $hoy = now()->startOfDay();

    $ordenesConEstado = $ordenes->map(function ($orden) use ($hoy) {

        //  Optimización: calcular una sola vez
        $fechaEntrega = $orden->fecha_entrega
            ? Carbon::parse($orden->fecha_entrega)->startOfDay()
            : null;

        $faltantes = $orden->detalles->sum('faltantes');
        $enviados  = $orden->detalles->sum('cantidad_enviada');

        $tieneFaltantes = $faltantes > 0;
        $tieneEnviados  = $enviados > 0;
        $tieneOT        = $orden->ordenTrabajo !== null;

        $fechaVencida = $fechaEntrega && $fechaEntrega->lt($hoy) && !$tieneEnviados && $orden->estado_id !== 5;

        //  Clasificación optimizada
        if ($orden->estado_id === 5) {
            $estado = 'Entrega Parcial';
        } elseif ($fechaVencida) {
            $estado = 'Vencida';
        } elseif ($tieneFaltantes && $tieneEnviados) {
            $estado = 'Con faltantes';
        } elseif ($tieneEnviados) {
            $estado = 'Lista';
        } elseif ($tieneOT) {
            $estado = 'En orden trabajo';
        } else {
            $estado = 'Registrada';
        }

        return [
            'id'            => $orden->id,
            'estado'        => $estado,
            'cliente'       => optional($orden->cliente)->nombre,
            'usuario'       => optional($orden->creador)->name,
            'fecha_entrega' => $orden->fecha_entrega,
        ];
    });

    //  Agrupaciones (se mantienen)
    $agrupadoPorEstado = $ordenesConEstado->groupBy('estado');
    $porCliente = $ordenesConEstado->groupBy('cliente')->map->count();
    $porUsuario = $ordenesConEstado->groupBy('usuario')->map->count();

    //  OPTIMIZACIÓN CRÍTICA (sin firstWhere)
    $listasRecientes = $agrupadoPorEstado->get('Lista', collect())
        ->filter(function ($o) use ($ordenesIndexadas) {
            $orden = $ordenesIndexadas[$o['id']] ?? null;
            return optional($orden?->ordenTrabajo)->updated_at > now()->subDays(5);
        });

    return response()->json([
        'total'              => $ordenes->count(),
        'registradas'        => $agrupadoPorEstado->get('Registrada', collect())->count(),
        'en_orden_trabajo'   => $agrupadoPorEstado->get('En orden trabajo', collect())->count(),
        'registradas_detalle'=> $agrupadoPorEstado->get('Registrada', collect())->pluck('cliente')->values(),
        'listas'             => $listasRecientes->count(),
        'faltantes'          => $agrupadoPorEstado->get('Con faltantes', collect())->count(),
        'vencidas'           => $agrupadoPorEstado->get('Vencida', collect())->count(),
        'hoy' => $ordenesConEstado
            ->filter(fn($o) =>
                Carbon::parse($o['fecha_entrega'])
                    ->startOfDay()
                    ->eq($hoy)
            )->count(),

        'en_orden_trabajo_detalle' => $agrupadoPorEstado->get('En orden trabajo', collect())->pluck('cliente')->values(),
        'listas_detalle'           => $listasRecientes->pluck('cliente')->values(),
        'faltantes_detalle'        => $agrupadoPorEstado->get('Con faltantes', collect())->pluck('cliente')->values(),
        'vencidas_detalle'         => $agrupadoPorEstado->get('Vencida', collect())->pluck('cliente')->values(),

        'por_cliente' => $porCliente,
        'por_usuario' => $porUsuario,

        'entrega_parcial'        => $agrupadoPorEstado->get('Entrega Parcial', collect())->count(),
        'entrega_parcial_detalle'=> $agrupadoPorEstado->get('Entrega Parcial', collect())->pluck('cliente')->values(),
    ]);
}

    // en App\Http\Controllers\Crm\DashboardController.php


public function getMonthlyStats(Request $request)
{
    $year  = $request->input('year', now()->year);
    $month = $request->input('month', now()->month);

    $start = Carbon::create($year, $month, 1)->startOfMonth()->startOfDay();
    $end   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

    // Órdenes cuyo compromiso de entrega es en el mes
    $ordenes = Orden_Compra::with('detalles.entregas')
        ->whereBetween('fecha_entrega', [$start, $end])
        ->where('estado_id', '!=', EstadoEnum::ENTREGA_PARCIAL->value)
        ->whereNot('estado_id', EstadoEnum::INACTIVO->value)
        ->get();

    $totalOrdenes = $ordenes->count();

    $entregadasATiempo = 0;
    $entregadasTarde = 0;
    $pendientes = 0;
    $despachadas = 0;

    foreach ($ordenes as $orden) {

        $fechaEntrega  = $orden->fecha_entrega ? Carbon::parse($orden->fecha_entrega)->endOfDay() : null;
        $ultimaEntrega = $orden->detalles
            ->flatMap(fn ($detalle) => $detalle->entregas)
            ->max('fecha_entrega');
        $fechaDespacho = null;

        if ($orden->fecha_despacho) {
            $fechaDespacho = Carbon::parse($orden->fecha_despacho);
        } elseif ($ultimaEntrega) {
            $fechaDespacho = Carbon::parse($ultimaEntrega);
        } elseif ((int) $orden->estado_id === EstadoEnum::COMPLETADO->value && $orden->updated_at) {
            $fechaDespacho = Carbon::parse($orden->updated_at);
        }

        // Si no tiene despacho
        if (!$fechaDespacho) {

            // si la fecha de entrega ya pasó → pendiente vencida
            if ($fechaEntrega && $fechaEntrega->lt(now()->startOfDay())) {
                $pendientes++;
            }

            continue;
        }

        $despachadas++;

        // comparar entrega vs despacho
        if ($fechaEntrega && $fechaDespacho->lte($fechaEntrega)) {
            $entregadasATiempo++;
        } else {
            $entregadasTarde++;
        }
    }

    $ordenesVencidas = $entregadasTarde + $pendientes;

    // KPI documento: (órdenes entregadas a tiempo / total órdenes) * 100
    $cumplimientoTotal = $totalOrdenes > 0
        ? ($entregadasATiempo / $totalOrdenes) * 100
        : 0;

    // KPI logístico (solo órdenes despachadas)
    $cumplimientoLogistico = $despachadas > 0
        ? ($entregadasATiempo / $despachadas) * 100
        : 0;

    return response()->json([
        'year' => $year,
        'month' => $month,

        'total_ordenes' => $totalOrdenes,

        'despachadas' => $despachadas,
        'entregadas_a_tiempo' => $entregadasATiempo,
        'entregadas_tarde' => $entregadasTarde,
        'pendientes_vencidas' => $pendientes,
        'ordenes_vencidas' => $ordenesVencidas,

        'cumplimiento_logistico_pct' => round($cumplimientoLogistico, 2),
        'cumplimiento_ot_pct' => round($cumplimientoTotal, 2),
    ]);
}




//Traer estadisticas de los clientes que mas compran
public function getTopClients(Request $request)
{
    $year = $request->input('year', now()->year);
    $month = $request->input('month', now()->month);
        $sedeId = $request->input('sede_id');
        $tipoOrden = $request->input('tipo_orden');
        $vendedorId = $request->input('vendedor_id');

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $clientes = Cliente::with(['ordenes' => function ($query) use ($start, $end, $sedeId, $tipoOrden, $vendedorId) {
            $query->whereBetween('fecha_entrega', [$start, $end]);

            if ($sedeId) {
                $query->where('sede_id', $sedeId);
            }
            if ($tipoOrden) {
                $query->where('tipo_orden', $tipoOrden);
            }
            if ($vendedorId) {
                $query->where('vendedor_id', $vendedorId);
            }
        }])->get();

        $topClients = $clientes->sortByDesc(fn($cliente) => $cliente->ordenes->count())
            ->take(10)
            ->values();

        return response()->json([
            'mes' => "$month/$year",
            'filtros_aplicados' => [
                'sede_id' => $sedeId,
                'tipo_orden' => $tipoOrden,
                'vendedor_id' => $vendedorId
            ],
            'clientes_top' => $topClients->map(function ($cliente) {
                return [
                    'cliente_id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'cantidad_ordenes' => $cliente->ordenes->count()
                ];
            })
        ]);
    }
    //Traer ordenes de trabajo con faltantes y vencidas a  entregar hoy para descargar



public function descargarOrdenesCriticasHoy(Request $request)
{
    $fecha = $request->filled('fecha')
        ? Carbon::parse($request->input('fecha'))->startOfDay()
        : now()->startOfDay();

    // ===============================
    // 1. VENCIDAS (NO ENVIADAS)
    // ===============================
    $vencidas = Orden_Compra::with(['detalles.product', 'cliente'])
        ->whereDate('fecha_entrega', '<', $fecha)
        ->whereNot('estado_id', EstadoEnum::INACTIVO->value)
        ->whereHas('detalles', function ($q) {
            $q->where('cantidad_enviada', 0);
        })
        ->get()
        ->unique('id')
        ->values();

    $idsUsados = $vencidas->pluck('id');

    // ===============================
    // 2. HOY (NO COMPLETADAS)
    // ===============================
    $hoy = Orden_Compra::with(['detalles.product', 'cliente'])
        ->whereDate('fecha_entrega', $fecha)
        ->where('estado_id', '!=', 2)
        ->whereNot('estado_id', EstadoEnum::INACTIVO->value)
        ->get()
        ->reject(fn($o) => $idsUsados->contains($o->id))
        ->unique('id')
        ->values();

    $idsUsados = $idsUsados->merge($hoy->pluck('id'));

    // ===============================
    // 3. CON FALTANTES
    // ===============================
    $conFaltantes = Orden_Compra::with(['detalles.product', 'cliente'])
        ->whereDate('fecha_entrega', '<=', $fecha)
        ->whereNot('estado_id', EstadoEnum::INACTIVO->value)
        ->whereHas('detalles', function ($q) {
            $q->where('faltantes', '>', 0);
        })
        ->get()
        ->reject(fn($o) => $idsUsados->contains($o->id))
        ->unique('id')
        ->values();

    // ===============================
    // 4. LIMPIAR DETALLES
    // ===============================
    $vencidas->each(function ($orden) {
        $orden->setRelation(
            'detalles',
            $orden->detalles
                ->filter(fn($d) => (int)($d->cantidad_enviada ?? 0) === 0)
                ->values()
        );
    });

    $conFaltantes->each(function ($orden) {
        $orden->setRelation(
            'detalles',
            $orden->detalles
                ->filter(fn($d) => (int)($d->faltantes ?? 0) > 0)
                ->values()
        );
    });

    // ===============================
    // 5. VALIDACIÓN
    // ===============================
    if ($vencidas->isEmpty() && $conFaltantes->isEmpty() && $hoy->isEmpty()) {
        return response()->json([
            'mensaje' => 'No hay órdenes críticas para la fecha.'
        ], 404);
    }

    // ===============================
    // 6. CONTROL DE CARGA (MUY IMPORTANTE)
    // ===============================
    $total = $vencidas->count() + $hoy->count() + $conFaltantes->count();

    if ($total > 300) {
        return response()->json([
            'error' => 'Demasiadas órdenes para generar el PDF. Aplica filtros.'
        ], 400);
    }

    // ===============================
    // 7. GENERAR PDF
    // ===============================
    $pdf = Pdf::loadView('pdf.ordenes_criticas', [
        'fecha'     => $fecha->toDateString(),
        'vencidas'  => $vencidas,
        'faltantes' => $conFaltantes,
        'hoy'       => $hoy,
    ]);

    $filename = 'ordenes_criticas_' . $fecha->format('Ymd') . '.pdf';

    return response($pdf->output(), 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
}


public function exportarOrdenesCriticasMes(Request $request)
{
    $year  = $request->input('year', now()->year);
    $month = $request->input('month', now()->month);

    $start = Carbon::create($year, $month, 1)->startOfDay();
    $end   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

    $ordenes = Orden_Compra::with(['detalles', 'cliente', 'sede', 'ordenTrabajo'])
        ->whereBetween('fecha_entrega', [$start, $end])
        ->whereNot('estado_id', EstadoEnum::INACTIVO->value)
        ->get();

    // Solo vencidas
    $vencidas = $ordenes->filter(function ($orden) {
        $fechaEntrega   = Carbon::parse($orden->fecha_entrega);
        $fechaDespacho  = $orden->fecha_despacho ?? $orden->updated_at;

        // Se considera vencida si la fecha de entrega ya pasó
        // y (no se entregó nada o se entregó después de la fecha)
        return $fechaEntrega->lt(now()->startOfDay()) &&
            (
                $orden->detalles->sum('cantidad_enviada') == 0 ||
                ($fechaDespacho && Carbon::parse($fechaDespacho)->gt($fechaEntrega))
            );
    });

    if ($vencidas->isEmpty()) {
        return response()->json(['mensaje' => 'No hay órdenes vencidas en este periodo.'], 404);
    }

    $export = new OrdenesCriticasExport($vencidas, collect(), collect(), $start);

    $filename = "ordenes_vencidas_{$year}_{$month}.xlsx";

    return Excel::download($export, $filename);
}

private function esVencida($orden)
{
    $fechaEntrega   = $orden->fecha_entrega ? Carbon::parse($orden->fecha_entrega) : null;
    $fechaDespacho  = $orden->fecha_despacho ? Carbon::parse($orden->fecha_despacho) : null;
    $enviados       = $orden->detalles->sum('cantidad_enviada');

    // 1. Si no tiene fecha de entrega, no evaluamos como vencida
    if (!$fechaEntrega) {
        return false;
    }

    // 2. Si no tiene despacho y la fecha de entrega ya pasó → vencida
    if (!$fechaDespacho && $fechaEntrega->lt(now()->startOfDay())) {
        return true;
    }

    // 3. Si tiene despacho pero fue después de la fecha de entrega → vencida
    if ($enviados > 0 && $fechaDespacho && $fechaDespacho->gt($fechaEntrega)) {
        return true;
    }

    return false;
}


public function getAuditData(Request $request)
{
    $year  = $request->input('year', now()->year);
    $month = $request->input('month', now()->month);

    $start = Carbon::create($year, $month, 1)->startOfDay();
    $end   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

    $table = (new Orden_Compra)->getTable();

    // 🔹 Incluimos generadas o despachadas en el rango
    // 🔹 Excluimos entregas parciales de la consulta principal
    $query = Orden_Compra::with([
        'detalles',
        'cliente',
        'creador',
        'ordenTrabajo',
        'sede',
        'estado',
    ])
    ->where(function ($q) use ($start, $end) {
        $q->whereBetween('created_at', [$start, $end])
          ->orWhereBetween('fecha_despacho', [$start, $end]);
    })
    ->where('estado_id', '!=', 5) // 👈 excluimos entregas parciales
    ->whereNot('estado_id', EstadoEnum::INACTIVO->value)
    ->select($table . '.*')
    ->distinct()
    ->orderBy($table . '.created_at', 'desc');

    // Filtros opcionales
    if ($request->filled('estado')) {
        $estadoMap = [
            'Pendiente'  => 1,
            'Completada' => 2,
            // 5 no lo incluimos aquí porque ya lo estamos excluyendo
        ];
        if (isset($estadoMap[$request->estado])) {
            $query->where('estado_id', $estadoMap[$request->estado]);
        }
    }
    if ($request->filled('cliente_id')) $query->where('cliente_id', $request->cliente_id);
    if ($request->filled('sede_id'))    $query->where('sede_id', $request->sede_id);
    if ($request->filled('creador_id')) $query->where('creador_id', $request->creador_id);

    $ordenes = $query->paginate(50);

    $auditoria = $ordenes->getCollection()->map(function ($orden) {
        $fechaEntrega  = $orden->fecha_entrega  ? Carbon::parse($orden->fecha_entrega)  : null;
        $fechaDespacho = $orden->fecha_despacho ? Carbon::parse($orden->fecha_despacho) : null;

        $esVencida = $this->esVencida($orden);

        $noEntregadoATiempo = false;
        $diasAtraso = 0;

        if ($fechaEntrega) {
            if ($fechaDespacho && $fechaDespacho->gt($fechaEntrega)) {
                $noEntregadoATiempo = true;
                $diasAtraso = $fechaEntrega->diffInDays($fechaDespacho);
            } elseif (!$fechaDespacho && now()->gt($fechaEntrega)) {
                $noEntregadoATiempo = true;
                $diasAtraso = $fechaEntrega->diffInDays(now());
            }
        }

        $enviadoTotal = (int) $orden->detalles->sum('cantidad_enviada');

        // 🔹 Estado final jerárquico (sin parciales, porque ya no llegan aquí)
        if ((int)$orden->estado_id === 2) {
            $estadoFinal = $noEntregadoATiempo ? 'Completada fuera de tiempo' : 'Completada a tiempo';
        } elseif ($enviadoTotal > 0 || $fechaDespacho) {
            $estadoFinal = $noEntregadoATiempo ? 'Despachada fuera de tiempo' : 'Despachada a tiempo';
        } elseif ($esVencida) {
            $estadoFinal = 'Vencida sin despacho';
        } else {
            $estadoFinal = 'Pendiente';
        }

        return [
            'id'                     => $orden->id,
            'cliente'                => optional($orden->cliente)->nombre,
            'creador'                => optional($orden->creador)->name,
            'fecha_creacion'         => optional($orden->created_at)->toDateString(),
            'fecha_actualizacion'    => optional($orden->updated_at)->toDateString(),
            'fecha_entrega'          => $fechaEntrega?->toDateString(),
            'fecha_despacho'         => $fechaDespacho?->toDateString(),
            'sede'                   => optional($orden->sede)->nombre,
            'estado_sistema'         => optional($orden->estado)->nombre,
            'estado_final'           => $estadoFinal,
            'vencida'                => (bool) $esVencida,
            'no_entregado_a_tiempo'  => (bool) $noEntregadoATiempo,
            'dias_atraso'            => $diasAtraso,
            'totales' => [
                'cantidad_solicitada' => (int) $orden->detalles->sum('cantidad_solicitada'),
                'cantidad_enviada'    => (int) $enviadoTotal,
                'faltantes'           => (int) $orden->detalles->sum('faltantes'),
            ],
            'orden_trabajo' => $orden->ordenTrabajo ? [
                'id'                  => $orden->ordenTrabajo->id,
                'fecha_creacion'      => optional($orden->ordenTrabajo->created_at)->toDateString(),
                'fecha_actualizacion' => optional($orden->ordenTrabajo->updated_at)->toDateString(),
            ] : null,
            'detalles' => $orden->detalles->map(fn ($d) => [
                'producto'            => $d->descripcion,
                'cantidad_solicitada' => (int) $d->cantidad_solicitada,
                'cantidad_enviada'    => (int) $d->cantidad_enviada,
                'faltantes'           => (int) $d->faltantes,
            ]),
        ];
    });

    $ordenes->setCollection($auditoria);

    return response()->json([
        'year'          => $year,
        'month'         => $month,
        'total_ordenes' => $ordenes->total(),
        'auditoria'     => $ordenes->items(),
        'pagination'    => [
            'current_page' => $ordenes->currentPage(),
            'per_page'     => $ordenes->perPage(),
            'last_page'    => $ordenes->lastPage(),
            'total'        => $ordenes->total(),
        ],
    ]);
}







}
