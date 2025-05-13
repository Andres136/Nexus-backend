<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\OrdenDeTrabajo;
use Illuminate\Http\Request;

class ordenTrabajoController extends Controller
{
     public function show($id)
    {
        $orden = OrdenDeTrabajo::with([
            'cliente',
            'user',
            'estado',
            'ordenCompra.detalles'
        ])->findOrFail($id);

        return response()->json($orden);
    }
}
