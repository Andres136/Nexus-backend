<?php

namespace App\Http\Controllers\Bsc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bsc\ActualizarEtapaRequest;
use App\Models\Bsc\BscEtapa;
use App\Services\Bsc\BscEtapaService;
use Illuminate\Http\Request;

class BscEtapaController extends Controller
{
    public function __construct(private BscEtapaService $service)
    {
    }

    /** GET /api/bsc/etapas?anio=YYYY */
    public function index(Request $request)
    {
        $anio = $request->query('anio');
        $anio = is_numeric($anio) ? (int) $anio : null;

        return response()->json($this->service->listar($anio));
    }

    /** PATCH /api/bsc/etapas/{etapa} */
    public function update(ActualizarEtapaRequest $request, BscEtapa $etapa)
    {
        return response()->json([
            'message' => 'Etapa actualizada',
            'data'    => $this->service->actualizar($etapa, $request->datosEtapa()),
        ]);
    }
}
