<?php

namespace App\Http\Controllers\Vsm;

use App\Http\Controllers\Controller;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Rutas\DeliveryEvent;
use App\Models\Vsm\Alistamiento;
use App\Services\Vsm\AlistamientoForecastService;
use App\Services\Vsm\VsmRuntimeService;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    protected $service;
    protected $runtimeService;

    public function __construct(AlistamientoForecastService $service)
    {
        $this->service = $service;
        $this->runtimeService = new VsmRuntimeService();
    }


        /**
     * Pronóstico global de todas las OT pendientes.
     */
    public function pronostico(Request $request)
    {
        $usuarios = $request->input('usuarios', 1);

        $data = $this->service->pronosticoGlobal($usuarios);

        return response()->json($data, 200);
    }
    
        /**
     * Pronóstico para una OT específica.
     */
    public function pronosticoOT($id, Request $request)
    {
        $usuarios = $request->input('usuarios', 1);

        $ot = OrdenDeTrabajo::with('ordenCompra.detalles')->findOrFail($id);

        $data = $this->service->estimarTiempoOT($ot, $usuarios);

        return response()->json([
            'orden_trabajo_id' => $ot->id,
            'cliente' => $ot->ordenCompra->cliente->nombre ?? null,
            'estimacion' => $data,
            'usuarios_asignados' => $usuarios
        ], 200);
    }


public function kpiProductividad()
{
    $data = $this->runtimeService->obtenerEficienciaPersonal([
        'sede_id'      => request('sede_id'),
        'fecha_inicio' => request('fecha_inicio') ?? now()->startOfMonth()->toDateString(),
        'fecha_fin'    => request('fecha_fin')    ?? now()->toDateString(),
    ]);

    return response()->json($data);
}

/**
 * GET /api/vsm/rendimiento?sede_id=&fecha_inicio=&fecha_fin=&tipo_periodo=diario|semanal|mensual
 *
 * Retorna producción y rendimiento agrupados por período.
 * rendimiento = (produccion_período / meta_período) × 100
 * meta_diaria  = meta_hora × (horas_semanales / 5)
 * meta_semanal = meta_hora × horas_semanales
 * meta_mensual = meta_hora × horas_semanales × (52/12)
 */
public function rendimientoPorPeriodo()
{
    $data = $this->runtimeService->obtenerRendimientoPorPeriodo([
        'sede_id'      => request('sede_id'),
        'fecha_inicio' => request('fecha_inicio') ?? now()->startOfMonth()->toDateString(),
        'fecha_fin'    => request('fecha_fin')    ?? now()->toDateString(),
        'tipo_periodo' => request('tipo_periodo', 'diario'),
    ]);

    return response()->json($data);
}



public function flujo()
{
    // 🔴 SUBQUERY: órdenes ya entregadas (NO deben aparecer en el flujo)
    $entregadasSubquery = function ($q) {
        $q->select('orden_id')
          ->from('delivery_events')
          ->where('estado', 'completado');
    };

    // 🟡 SUBQUERY: órdenes que ya entraron a alistamiento
    $alistamientoSubquery = function ($q) {
        $q->select('orden_trabajo_id')
          ->from('alistamiento');
    };

    // 🔴 1️⃣ PENDIENTES (incluye estado 1 y 5, pero que NO hayan avanzado)
    $pendientes = OrdenDeTrabajo::whereIn('estado_id', [1, 5])
        ->whereNotIn('id', $alistamientoSubquery) // ❌ ya no debe estar en pendientes si está en alistamiento
        ->whereNotIn('id', $entregadasSubquery)   // ❌ excluir entregadas
        ->with('cliente:id,nombre')
        ->get();

    // 🟡 2️⃣ ALISTANDO
    $alistando = Alistamiento::whereIn('estado', [
            'INICIADO',
            'EN_PROGRESO',
            'PAUSADO'
        ])
        ->whereNotIn('orden_trabajo_id', $entregadasSubquery) // ❌ excluir entregadas
        ->with(['ordenTrabajo.cliente:id,nombre', 'detalles'])
        ->get();

    // 🔵 3️⃣ FINALIZADAS (listas para despacho)
$finalizadas = Alistamiento::where('estado', 'FINALIZADO')
    ->whereNotIn('orden_trabajo_id', function ($q) {
        $q->select('orden_id')->from('delivery_events');
    })
    ->select('orden_trabajo_id')
    ->distinct()
    ->with(['ordenTrabajo.cliente:id,nombre'])
    ->get();
    // 🟢 4️⃣ EN RUTA
    $delivery = DeliveryEvent::whereIn('estado', ['pendiente', 'en_ruta'])
        ->whereNotIn('orden_id', $entregadasSubquery) // ❌ excluir entregadas
        ->with(['orden.cliente:id,nombre'])
        ->get();

    return response()->json([
        'pendientes' => $pendientes,
        'alistando'  => $alistando,
        'finalizadas'=> $finalizadas,
        'delivery'   => $delivery,
    ]);
}

}
