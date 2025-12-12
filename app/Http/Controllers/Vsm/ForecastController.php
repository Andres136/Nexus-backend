<?php

namespace App\Http\Controllers\Vsm;

use App\Http\Controllers\Controller;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Rutas\DeliveryEvent;
use App\Models\Vsm\Alistamiento;
use App\Services\Vsm\AlistamientoForecastService;
use Illuminate\Http\Request;
use Mockery\Matcher\Any;

class ForecastController extends Controller
{
    protected $service;

    public function __construct(AlistamientoForecastService $service)
    {
        $this->service = $service;
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



     public function flujo()
    {
        // ----------------------------------------------------------
        // 1️⃣ PENDIENTES: estado_id = 2 (pendiente) o 5 (parcial)
        // ----------------------------------------------------------
        $pendientes = OrdenDeTrabajo::whereIn('estado_id', [2, 5])
            ->with('cliente')
            ->get();

        // ----------------------------------------------------------
        // 2️⃣ ALISTANDO: alistamientos activos
        // ----------------------------------------------------------
        $alistando = Alistamiento::whereIn('estado', [
                'INICIADO',
                'EN_PROGRESO',
                'PAUSADO'
            ])
            ->with(['ordenTrabajo.cliente', 'detalles'])
            ->get();

        // ----------------------------------------------------------
        // 3️⃣ FINALIZADAS SIN DELIVERY
        // OT finalizada de Alistamiento pero sin evento de delivery
        // ----------------------------------------------------------
        $finalizadas =Alistamiento::where('estado', 'FINALIZADO')
            ->with(['ordenTrabajo.cliente'])
            ->get();

        // ----------------------------------------------------------
        // 4️⃣ DELIVERY PENDIENTE
        // ----------------------------------------------------------
        $delivery = DeliveryEvent::where('estado', 'pendiente')
            ->with(['orden.cliente'])
            ->get();

        return response()->json([
            'pendientes' => $pendientes,
            'alistando'  => $alistando,
            'finalizadas'=> $finalizadas,
            'delivery'   => $delivery,
        ]);
    }
}
