<?php

namespace App\Http\Controllers\Bsc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bsc\ClasificarIndicadorRequest;
use App\Http\Requests\Bsc\DashboardBscRequest;
use App\Http\Requests\Bsc\EjecutarSnapshotRequest;
use App\Http\Requests\Bsc\ListarIndicadoresBscRequest;
use App\Models\Indicadores;
use App\Services\Bsc\BscDashboardService;
use App\Services\Bsc\BscIndicadorService;
use App\Services\Bsc\BscSnapshotService;

class BscController extends Controller
{
    public function __construct(
        private BscDashboardService $dashboardService,
        private BscIndicadorService $indicadorService,
    ) {
    }

    /** GET /api/bsc/dashboard */
    public function dashboard(DashboardBscRequest $request)
    {
        return response()->json(
            $this->dashboardService->construir($request->periodo(), $request->historial())
        );
    }

    /** GET /api/bsc/indicadores — catálogo existente para clasificar */
    public function indicadores(ListarIndicadoresBscRequest $request)
    {
        return response()->json([
            'data' => $this->indicadorService->listar($request->departamentoId(), $request->search()),
        ]);
    }

    /** PATCH /api/bsc/indicadores/{indicador}/clasificar */
    public function clasificar(ClasificarIndicadorRequest $request, Indicadores $indicador)
    {
        $data = $this->indicadorService->clasificar($indicador, $request->datosClasificacion());

        return response()->json(['message' => 'Indicador clasificado', 'data' => $data]);
    }

    /** GET /api/bsc/calculators */
    public function calculators()
    {
        return response()->json(['data' => $this->indicadorService->calculatorsDisponibles()]);
    }

    /** GET /api/bsc/objetivos */
    public function objetivos()
    {
        return response()->json(['data' => $this->indicadorService->objetivosUsados()]);
    }

    /** POST /api/bsc/snapshot — dispara el cálculo automático a demanda */
    public function snapshot(EjecutarSnapshotRequest $request, BscSnapshotService $service)
    {
        return response()->json([
            'message'    => 'Snapshot ejecutado',
            'periodo'    => $request->periodo() ?? now()->format('Y-m'),
            'resultados' => $service->ejecutar($request->periodo()),
        ]);
    }
}
