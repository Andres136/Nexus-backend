<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreAnalisisProductoNoConformeRequest;
use App\Services\Hseq\AnalisisProductoNoConformeService;
use Illuminate\Http\Request;

class AnalisisProductoNoConformeController extends Controller
{
    protected AnalisisProductoNoConformeService $service;

    public function __construct(AnalisisProductoNoConformeService $service)
    {
        $this->service = $service;
    }

    public function store(StoreAnalisisProductoNoConformeRequest $request)
    {
        $data = array_merge($request->validated(), [
            'analista_id' => $request->user()->id,
        ]);
        $archivo = $request->file('archivo_evidencia');

        $analisis = $this->service->crear($data, $archivo);

        return response()->json([
            'message' => 'Análisis creado exitosamente',
            'data' => $analisis,
        ], 201);
    }

    public function show(int $id)
    {
        $analisis = $this->service->show($id);

        return response()->json([
            'message' => 'Análisis encontrado',
            'data' => $analisis,
        ]);
    }

    public function showByProducto(int $productoNoConformeId)
    {
        $analisis = $this->service->showByProducto($productoNoConformeId);

        return response()->json([
            'message' => 'Análisis encontrado',
            'data' => $analisis,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $analisis = $this->service->actualizar($id, $request->all());

        return response()->json([
            'message' => 'Análisis actualizado exitosamente',
            'data' => $analisis,
        ]);
    }

    public function cambiarEstado(Request $request, int $id)
    {
        $request->validate([
            'estado_id' => 'required|exists:estados,id',
        ]);

        $analisis = $this->service->cambiarEstado($id, $request->estado_id);

        return response()->json([
            'message' => 'Estado del análisis actualizado exitosamente',
            'data' => $analisis,
        ]);
    }
}
