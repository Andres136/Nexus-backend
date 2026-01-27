<?php

namespace App\Http\Controllers\Traslados;

use App\Exceptions\Traslados\MovimientoInventarioInvalidoException;
use App\Http\Controllers\Controller;
use App\Models\Traslados\Traslado_Bodega;
use App\Services\Traslados\TrasladoBodegaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Stmt\TryCatch;

class TrasladosInventarioEmailController extends Controller
{
    public function __construct(protected TrasladoBodegaService $service)
    {
  
    }

    public function aprobar(Traslado_Bodega $traslado)
    {

        try {
            Auth::loginUsingId(request()->query('user'));
            $this->service->aprobar($traslado->id);
            return view('emails.traslados.respuesta', [
                'mensaje' => 'Traslado aprobado por inventario correctamente'
            ]);
        } catch (MovimientoInventarioInvalidoException $e) {
            return view('errors.movimiento-inventario', [
                'mensaje' => 'Error al aprobar el traslado: ' . $e->getMessage()
            ]);
        }
    }
}
