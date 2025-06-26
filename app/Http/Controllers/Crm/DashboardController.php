<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Cliente;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenDeTrabajo;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $fechaVencida = $fechaEntrega->lt($hoy) && ! $tieneEnviados;
    
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
            'listas'             => $listasRecientes->count(),
            'faltantes'          => $agrupadoPorEstado->get('Con faltantes', collect())->count(),
            'vencidas'           => $agrupadoPorEstado->get('Vencida', collect())->count(),
            'hoy'                => $ordenesConEstado
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

    $start = Carbon::create($year, $month, 1)->startOfDay();
    $end   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

    $ordenes = Orden_Compra::with('detalles')
        ->whereBetween('fecha_entrega', [$start, $end])
        ->where('estado_id', '!=', 5) // ❌ Excluir Entrega Parcial
        ->get();

    $total       = $ordenes->count();
    $despachadas = $ordenes->filter(fn($o) => $o->detalles->sum('cantidad_enviada') > 0)->count();
    $vencidas    = $ordenes->filter(fn($o) =>
        Carbon::parse($o->fecha_entrega)->lt(now()->startOfDay()) &&
        $o->detalles->sum('cantidad_enviada') == 0
    )->count();
    $pendientes  = $total - $despachadas - $vencidas;

    return response()->json(compact(
        'year', 'month', 'total', 'despachadas', 'vencidas', 'pendientes'
    ));
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


}
