<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreConsumoServicioRequest;
use App\Services\Hseq\ConsumoServicioService;
use Illuminate\Http\Request;

class ConsumoServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $consumoServicioService;
    public function __construct(ConsumoServicioService $consumoServicioService)
    {
        $this->consumoServicioService = $consumoServicioService;
    }
 public function index(Request $request)
{
    $data = $this->consumoServicioService->getAll($request->all());

    return response()->json($data);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreConsumoServicioRequest $request)
    {
        $consumoServicio = $this->consumoServicioService->create($request->all());
        return response()->json([
            'message' => 'Consumo de servicio creado exitosamente',
            'data' => $consumoServicio
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $consumoServicio = $this->consumoServicioService->find($id);
        return response()->json([
            'data' => $consumoServicio
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $consumoServicio = $this->consumoServicioService->update($id, $request->all());
        return response()->json([
            'message' => 'Consumo de servicio actualizado exitosamente',
            'data' => $consumoServicio
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->consumoServicioService->delete($id);
        return response()->json([
            'message' => 'Consumo de servicio eliminado exitosamente'
        ], 200);
    }

    /**
     * Obtener estadísticas anuales de consumo por tipo de servicio
     */
public function estadisticasAnuales(Request $request)
{
    $filters = [
        'anio' => $request->query('year', date('Y')),
        'tipo_servicio_id' => $request->query('tipo_servicio_id'),
        'sede_id' => $request->query('sede_id'),
        'search' => $request->query('search'),
    ];

    $data = $this->consumoServicioService->consumoTimeline($filters);

    return response()->json($data, 200);
}
}
