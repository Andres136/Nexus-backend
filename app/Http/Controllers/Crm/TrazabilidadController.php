<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Crm\TrazabilidadService;
use Illuminate\Http\Request;

class TrazabilidadController extends Controller
{
    protected TrazabilidadService $service;

    public function __construct(TrazabilidadService $service)
    {
        $this->service = $service;
    }

    public function buscarOrdenesCompra(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        return response()->json(['data' => $this->service->buscarOrdenesCompra($search)]);
    }

    public function ordenCompraDetalle(int $id)
    {
        return response()->json(['data' => $this->service->ordenCompraDetalle($id)]);
    }

    public function productoTrazabilidad(int $productoId)
    {
        return response()->json(['data' => $this->service->productoTrazabilidad($productoId)]);
    }
}
