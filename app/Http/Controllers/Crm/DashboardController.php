<?php

namespace App\Http\Controllers\Crm;

use App\Exports\OrdenesCriticasExport;
use App\Http\Controllers\Controller;
use App\Models\Crm\Cliente;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenDeTrabajo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{



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

    $start = Carbon::create($year, $month, 1)->startOfDay();
    $end   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

    // 🔹 Filtrar órdenes por fecha_despacho
    $ordenes = Orden_Compra::with('detalles')
        ->whereBetween('fecha_despacho', [$start, $end])
        ->where('estado_id', '!=', 5) // excluir parciales
        ->get();

    $total = $ordenes->count();

    // Despachadas a tiempo: tienen fecha_despacho y fue <= fecha_entrega
    $despachadas = $ordenes->filter(function ($o) {
        $fechaEntrega  = $o->fecha_entrega ? Carbon::parse($o->fecha_entrega) : null;
        $fechaDespacho = $o->fecha_despacho ? Carbon::parse($o->fecha_despacho) : null;
        $enviados      = $o->detalles->sum('cantidad_enviada');

        return $enviados > 0 && $fechaDespacho && $fechaEntrega && $fechaDespacho->lte($fechaEntrega);
    })->count();

    // Vencidas (usando tu método reutilizable)
    $vencidas = $ordenes->filter(fn($o) => $this->esVencida($o))->count();

    // Pendientes (estado_id = 1)
    $pendientes = $ordenes->filter(fn($o) => (int)$o->estado_id === 1)->count();

    return response()->json([
        'year'        => $year,
        'month'       => $month,
        'total'       => $total,
        'despachadas' => $despachadas,
        'vencidas'    => $vencidas,
        'pendientes'  => $pendientes,
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

    $ordenes = Orden_Compra::with(['detalles', 'cliente'])->get();

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
    $year = $request->input('year', now()->year);
    $month = $request->input('month', now()->month);

    // Rango de fechas basado en la fecha de despacho
    $start = Carbon::create($year, $month, 1)->startOfDay();
    $end   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

    // Consulta principal
    $query = Orden_Compra::with([
        'detalles',
        'cliente',
        'creador',
        'ordenTrabajo',
        'sede',
        'estado'
    ])->whereBetween('fecha_despacho', [$start, $end]);

    // Mapeo de estados a sus IDs correspondientes
    $estadoMap = [
        'Pendiente' => 1,
        'Completada' => 2,
     
        'Entrega Parcial' => 5,

    ];

    // Filtros adicionales
    if ($request->filled('estado')) {
        $estadoId = $estadoMap[$request->estado] ?? null;
        if ($estadoId !== null) {
            $query->where('estado_id', $estadoId);
        }
    }
    if ($request->filled('cliente_id')) {
        $query->where('cliente_id', $request->cliente_id);
    }
    if ($request->filled('sede_id')) {
        $query->where('sede_id', $request->sede_id);
    }
    if ($request->filled('creador_id')) {
        $query->where('creador_id', $request->creador_id);
    }

    $ordenes = $query->paginate(50);

    $auditoria = $ordenes->map(function ($orden) {
        $fechaEntrega  = $orden->fecha_entrega ? Carbon::parse($orden->fecha_entrega) : null;
        $fechaDespacho = $orden->fecha_despacho ? Carbon::parse($orden->fecha_despacho) : null;

        $esVencida = $this->esVencida($orden);

        // Regla: no entregada a tiempo
        $noEntregadoATiempo = false;
        if ($fechaDespacho && $fechaEntrega) {
            $noEntregadoATiempo = $fechaDespacho->gt($fechaEntrega);
        } elseif (!$fechaDespacho && $fechaEntrega) {
            $noEntregadoATiempo = now()->gt($fechaEntrega);
        }

        // Estado final jerárquico
        $estadoFinal = 'Pendiente';
        if ($esVencida) {
            $estadoFinal = 'Vencida';
        } elseif ($orden->estado_id == 2) { // ejemplo: completado
            $estadoFinal = 'Completada';
        } elseif ($orden->estado_id == 5) {
            $estadoFinal = 'Entrega Parcial';
        } elseif ($orden->detalles->sum('cantidad_enviada') > 0) {
            $estadoFinal = 'Despachada';
        }

        return [
            'id'                  => $orden->id,
            'cliente'             => optional($orden->cliente)->nombre,
            'creador'             => optional($orden->creador)->name,
            'fecha_creacion'      => $orden->created_at->format('d/m/Y'),
            'fecha_actualizacion' => $orden->updated_at->format('d/m/Y'),
            'fecha_entrega'       => $fechaEntrega ? $fechaEntrega->format('d/m/Y') : null,
            'fecha_despacho'      => $fechaDespacho ? $fechaDespacho->format('d/m/Y') : null,
            'estado_final'        => $estadoFinal,
            'detalles' => $orden->detalles->map(fn($detalle) => [
                'producto'            => $detalle->descripcion,
                'cantidad_solicitada' => $detalle->cantidad_solicitada,
                'cantidad_enviada'    => $detalle->cantidad_enviada,
                'faltantes'           => $detalle->faltantes,
            ]),
            'orden_trabajo' => $orden->ordenTrabajo ? [
                'id'                  => $orden->ordenTrabajo->id,
                'fecha_creacion'      => $orden->ordenTrabajo->created_at->format('d/m/Y'),
                'fecha_actualizacion' => $orden->ordenTrabajo->updated_at->format('d/m/Y'),
            ] : null,
            'sede'                 => optional($orden->sede)->nombre,
            'vencida'              => $esVencida,
            'no_entregado_a_tiempo'=> $noEntregadoATiempo,
        ];
    });

    return response()->json([
        'year'          => $year,
        'month'         => $month,
        'total_ordenes' => $ordenes->total(),
        'auditoria'     => $auditoria,
    ]);
}






}
