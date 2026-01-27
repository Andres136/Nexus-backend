<?php

namespace App\Http\Controllers\Traslados;

use App\Exceptions\Traslados\EstadoTrasladoInvalidoException;
use App\Http\Controllers\Controller;
use App\Models\Traslados\Traslado_Bodega;
use App\Services\Traslados\TrasladoBodegaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrasladosBodegaEmailController extends Controller
{
    public function __construct(protected TrasladoBodegaService $service)
    { }

   public function aprobar(Traslado_Bodega $traslado)
{
    try {
        Auth::loginUsingId(request()->query('user'));

        $this->service->aprobarPorBodega($traslado->id, true);

        return view('emails.traslados.aprobado_bodega', [
            'traslado' => $traslado
        ]);

    } catch (EstadoTrasladoInvalidoException $e) {

        return view('errors.estado-traslado', [
            'mensaje' => $e->getMessage(),
        ]);
    }
}



    public function rechazar(Traslado_Bodega $traslado)
    {
        Auth::loginUsingId(request()->query('user'));

        $this->service->aprobarPorBodega($traslado->id,false);
        return view('emails.traslados.rechazado_bodega', compact('traslado'));
    }
}
