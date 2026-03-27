<?php

namespace App\Http\Controllers\Crm;

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


public function kpis(Request $request, OrdenCompraService $ordenCompraService)
{

$ordenCompraService->notificarOrdenesPorVencer(); // Ejecuta la lógica de notificación
    $year = $request->get('year');

    return response()->json(
        $this->kpiService->getDashboardKpisYearly($year)
    );
}
    public function getDashboardData()
    {
        $ordenes = Orden_Compra::with('ordenTrabajo', 'detalles', 'cliente', 'creador')->get();
        // Defino "hoy" a las 00:00:00
        $hoy = now()->startOfDay();

        $ordenesConEstado = $ordenes->map(function ($orden) use ($hoy) {
            // Parseo fecha de entrega y la pongo también al inicio del día
            $fechaEntrega = Carbon::parse($orden->fecha_entrega)->startOfDay();

            $tieneFaltantes = $orden->detalles->sum('faltantes') > 0;
            $tieneEnviados  = $orden->detalles->sum('cantidad_enviada') > 0;
            $tieneOT        = $orden->ordenTrabajo !== null;

            // Solo se considera vencida si la fecha_entrega es ANTERIOR a hoy
            // y no se ha enviado nada
            $fechaVencida = $fechaEntrega->lt($hoy) && ! $tieneEnviados && $orden->estado_id !== 5;

            // ✅ Clasificación considerando "Entrega Parcial"
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
                'id'             => $orden->id,
                'estado'         => $estado,
                'cliente'        => optional($orden->cliente)->nombre,
                'usuario'        => optional($orden->creador)->name,
                'fecha_entrega'  => $orden->fecha_entrega,
            ];
        });

        $agrupadoPorEstado = $ordenesConEstado->groupBy('estado');
        $porCliente = $ordenesConEstado->groupBy('cliente')->map->count();
        $porUsuario = $ordenesConEstado->groupBy('usuario')->map->count();

        $listasRecientes = $agrupadoPorEstado->get('Lista', collect())
            ->filter(fn($o) => optional(
                $ordenes->firstWhere('id', $o['id'])
            )->ordenTrabajo->updated_at > now()->subDays(5));

        return response()->json([
            'total'              => $ordenes->count(),
            'registradas'        => $agrupadoPorEstado->get('Registrada', collect())->count(),
            'en_orden_trabajo'   => $agrupadoPorEstado->get('En orden trabajo', collect())->count(),
            'registradas_detalle'=> $agrupadoPorEstado->get('Registrada', collect())->pluck('cliente')->values(),
            'listas'             => $listasRecientes->count(),
            'faltantes'          => $agrupadoPorEstado->get('Con faltantes', collect())->count(),
            'vencidas'           => $agrupadoPorEstado->get('Vencida', collect())->count(),
            'hoy'                => $ordenesConEstado
                ->filter(
                    fn($o) =>
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
            'entrega_parcial_detalle' => $agrupadoPorEstado->get('Entrega Parcial', collect())->pluck('cliente')->values(),

        ]);
    }



    // en App\Http\Controllers\Crm\DashboardController.php


public function getMonthlyStats(Request $request)
{
    $year  = $request->input('year', now()->year);
    $month = $request->input('month', now()->month);

    $start = Carbon::create($year, $month, 1)->startOfMonth();
    $end   = Carbon::create($year, $month, 1)->endOfMonth();

    // Órdenes cuyo compromiso de entrega es en el mes
    $ordenes = Orden_Compra::with('detalles')
        ->whereBetween('fecha_entrega', [$start, $end])
        ->where('estado_id', '!=', 5) // excluir parciales
        ->get();

    $totalOrdenes = $ordenes->count();

    $entregadasATiempo = 0;
    $entregadasTarde = 0;
    $pendientes = 0;
    $despachadas = 0;

    foreach ($ordenes as $orden) {

        $fechaEntrega  = $orden->fecha_entrega ? Carbon::parse($orden->fecha_entrega) : null;
        $fechaDespacho = $orden->fecha_despacho ? Carbon::parse($orden->fecha_despacho) : null;

        // Si no tiene despacho
        if (!$fechaDespacho) {

            // si la fecha de entrega ya pasó → pendiente vencida
            if ($fechaEntrega && $fechaEntrega->lt(now())) {
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

    // KPI cumplimiento general
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

        'cumplimiento_total_pct' => round($cumplimientoTotal, 2),
        'cumplimiento_logistico_pct' => round($cumplimientoLogistico, 2)
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

    $ordenes = Orden_Compra::with(['detalles.product', 'cliente'])->get();

    // 1) Buckets iniciales
    $vencidas = $ordenes->filter(function ($orden) use ($fecha) {
        $enviados = $orden->detalles->sum('cantidad_enviada');
        return $orden->fecha_entrega
            && Carbon::parse($orden->fecha_entrega)->lt($fecha)
            && $enviados == 0;
    });

    $hoy = $ordenes->filter(function ($orden) use ($fecha) {
        return $orden->fecha_entrega
            && Carbon::parse($orden->fecha_entrega)->startOfDay()->eq($fecha);
    });

    $conFaltantes = $ordenes->filter(function ($orden) use ($fecha) {
        $tieneFaltantes = $orden->detalles->sum('faltantes') > 0;
        $enviados = $orden->detalles->sum('cantidad_enviada');
        $vencida = $orden->fecha_entrega
            && Carbon::parse($orden->fecha_entrega)->lt($fecha)
            && $enviados == 0;
        // Solo incluye si la fecha_entrega es <= fecha consultada
        return $tieneFaltantes
            && !$vencida
            && $orden->fecha_entrega
            && Carbon::parse($orden->fecha_entrega)->lte($fecha);
    });

    // 2) Exclusividad por prioridad: VENCIDAS > HOY > FALTANTES
    $vencidas     = $vencidas->unique('id')->values();
    $idsUsados    = $vencidas->pluck('id');
$hoy = $ordenes->filter(function ($orden) use ($fecha) {
    return $orden->fecha_entrega
        && Carbon::parse($orden->fecha_entrega)->startOfDay()->eq($fecha)
        && $orden->estado_id !== 2; // Excluye completadas
});
   
    $idsUsados    = $idsUsados->merge($hoy->pluck('id'));

    $conFaltantes = $conFaltantes->reject(fn($o) => $idsUsados->contains($o->id))
        ->unique('id')->values();

    // 3) Limpiar detalles SIN modificar la misma instancia usada en otros buckets
    $vencidas->each(function ($orden) {
        $orden->setRelation(
            'detalles',
            $orden->detalles->filter(fn($d) => (int)($d->cantidad_enviada ?? 0) === 0)->values()
        );
    });

    $conFaltantes->each(function ($orden) {
        $orden->setRelation(
            'detalles',
            $orden->detalles->filter(fn($d) => (int)($d->faltantes ?? 0) > 0)->values()
        );
    });

    if ($vencidas->isEmpty() && $conFaltantes->isEmpty() && $hoy->isEmpty()) {
        return response()->json(['mensaje' => 'No hay órdenes críticas para la fecha.'], 404);
    }
    $productosIds = $ordenes
    ->flatMap(fn($o) => $o->detalles->pluck('producto_id'))
    ->unique();

$inventarios = Inventario::with(['producto', 'empresa', 'sede', 'bodega'])
    ->where('stock', '>', 0) 
    ->get();

    $pdf = Pdf::loadView('pdf.ordenes_criticas', [
        'fecha'     => $fecha->toDateString(),
        'vencidas'  => $vencidas,
        'faltantes' => $conFaltantes,
        'hoy'       => $hoy,
        'inventarios' => $inventarios
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
