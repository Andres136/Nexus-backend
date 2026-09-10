<?php

namespace App\Http\Controllers\Bsc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bsc\ListarPlanesRequest;
use App\Services\Bsc\BscPlanService;

class BscPlanController extends Controller
{
    public function __construct(private BscPlanService $service)
    {
    }

    /** GET /api/bsc/planes — registros de indicadores con análisis/plan adjunto */
    public function index(ListarPlanesRequest $request)
    {
        return response()->json([
            'data' => $this->service->listar($request->periodo(), $request->perspectiva()),
        ]);
    }
}
