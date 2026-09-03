<?php

namespace App\Http\Controllers\Bsc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bsc\ActualizarPerspectivaRequest;
use App\Models\Bsc\BscPerspectiva;
use App\Services\Bsc\BscPerspectivaService;

class BscPerspectivaController extends Controller
{
    public function __construct(private BscPerspectivaService $service)
    {
    }

    /** GET /api/bsc/perspectivas */
    public function index()
    {
        return response()->json(['data' => $this->service->listar()]);
    }

    /** PATCH/POST /api/bsc/perspectivas/{perspectiva} */
    public function update(ActualizarPerspectivaRequest $request, BscPerspectiva $perspectiva)
    {
        $data = $this->service->actualizar(
            $perspectiva,
            $request->datosPerspectiva(),
            $request->file('icono'),
            $request->departamentos(),
        );

        return response()->json(['message' => 'Perspectiva actualizada', 'data' => $data]);
    }

    /** DELETE /api/bsc/perspectivas/{perspectiva}/icono */
    public function eliminarIcono(BscPerspectiva $perspectiva)
    {
        $this->service->eliminarIcono($perspectiva);

        return response()->json(['message' => 'Icono eliminado']);
    }
}
