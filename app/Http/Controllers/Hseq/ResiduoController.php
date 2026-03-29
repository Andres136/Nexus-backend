<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreResiduoRequest;
use App\Services\Hseq\ResiduoService;
use Illuminate\Http\Request;

class ResiduoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $residuoService;
    public function __construct(ResiduoService $residuoService)
    {
        $this->residuoService = $residuoService;
    }   
public function index(Request $request)
{
    $data = $this->residuoService->getAll($request->all());

    return response()->json($data);
}
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResiduoRequest $request)
    {
        $data = $request->validated();
        $residuo = $this->residuoService->create($data);
        return response()->json([
            'message' => 'Residuo creado exitosamente',
            'data' => $residuo
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $residuo = $this->residuoService->find($id);
        return response()->json($residuo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->all();
        $residuo = $this->residuoService->update($id, $data);
        return response()->json([
            'message' => 'Residuo actualizado exitosamente',
            'data' => $residuo
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->residuoService->delete($id);
        return response()->json([
            'message' => 'Residuo eliminado exitosamente'
        ]);
    }

    public function estadisticasAnuales(Request $request)
    {
         $filters = [
        'anio' => $request->query('year', date('Y')),
        'tipo_servicio_id' => $request->query('tipo_servicio_id'),
        'sede_id' => $request->query('sede_id'),
        'search' => $request->query('search'),
    ];
        $response = $this->residuoService->residuosTimeline($filters);

        return response()->json($response, 200);
    }
}
