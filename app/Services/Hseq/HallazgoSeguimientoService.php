<?php

namespace App\Services\Hseq;

use App\Models\Hseq\HallazgoSeguimento;

class HallazgoSeguimientoService
{

public function crearSeguimiento($hallazgoId, $observacion)
    {
        return HallazgoSeguimento::create([
            'hallazgo_id' => $hallazgoId,
            'usuario_id' => auth()->id(),
            'observacion' => $observacion,
            'fecha' => now(),
        ]);
    }

    //listar seguimientos de un hallazgo
    public function listarSeguimientos($hallazgoId)
    {
        return HallazgoSeguimento::where('hallazgo_id', $hallazgoId)->with('usuario')->get();
    }
}