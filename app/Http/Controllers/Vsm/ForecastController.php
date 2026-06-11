<?php

namespace App\Http\Controllers\Vsm;

use App\Http\Controllers\Controller;
use App\Models\Crm\OrdenDeTrabajo;
use App\Services\Vsm\AlistamientoForecastService;
use App\Services\Vsm\VsmFlowService;
use App\Services\Vsm\VsmRuntimeService;
use App\Services\Vsm\VsmSupplyCoverageService;
use App\Services\Vsm\VsmCapacityService;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    protected $service;
    protected $runtimeService;
    protected VsmFlowService $flowService;
    protected VsmSupplyCoverageService $supplyCoverageService;
    protected VsmCapacityService $capacityService;

    public function __construct(
        AlistamientoForecastService $service,
        VsmFlowService $flowService,
        VsmSupplyCoverageService $supplyCoverageService,
        VsmCapacityService $capacityService
    )
    {
        $this->service = $service;
        $this->runtimeService = new VsmRuntimeService();
        $this->flowService = $flowService;
        $this->supplyCoverageService = $supplyCoverageService;
        $this->capacityService = $capacityService;
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



public function flujo(Request $request)
{
    return response()->json($this->flowService->obtenerFlujo(
        $request->user(),
        $request->only(['sede_id', 'fecha_inicio', 'fecha_fin', 'umbral_horas'])
    ));
}

public function coberturaAbastecimiento(Request $request)
{
    return response()->json($this->supplyCoverageService->obtenerCobertura(
        $request->user(),
        $request->only(['sede_id', 'search', 'estado', 'per_page', 'page'])
    ));
}

public function capacidad(Request $request)
{
    return response()->json($this->capacityService->calcular(
        $request->user(),
        $request->only(['usuarios', 'dias_objetivo', 'sede_id'])
    ));
}

}
