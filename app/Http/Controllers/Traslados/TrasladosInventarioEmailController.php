<?php

namespace App\Http\Controllers\Traslados;

use App\Http\Controllers\Controller;
use App\Models\Traslados\Traslado_Bodega;
use App\Services\Traslados\TrasladoBodegaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrasladosInventarioEmailController extends Controller
{
    public function __construct(protected TrasladoBodegaService $service)
    {
  
    }

    public function aprobar(Traslado_Bodega $traslado)
    {
        Auth::loginUsingId(request()->query('user'));

        $this->service->aprobar($traslado->id);

        return view('emails.traslados.respuesta', [
            'mensaje' => 'Traslado aprobado por inventario correctamente'
        ]);
    }
}
