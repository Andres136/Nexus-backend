<?php

namespace App\Http\Controllers\Tic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tic\StoreAsignacionEquipoRequest;
use Illuminate\Http\Request;

class AsignacionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $asignacionesService;

    public function __construct(\App\Services\Tic\AsignacionesService $asignacionesService)
    {
        $this->asignacionesService = $asignacionesService;
    }
public function index(Request $request)
{
$filters = $request->only([
    'usuario_id',
    'empresa_id',
    'activo',
    'search',
    'per_page'
]);

    $data = $this->asignacionesService->getAllAsignaciones($filters);

    return response()->json($data);
}

public function byUsuario(Request $request, int $userId)
{
    $soloActivas = ! $request->boolean('incluir_inactivas');

    return response()->json(
        $this->asignacionesService->getAsignacionesByUsuario($userId, $soloActivas)
    );
}
    /**
     * Store a newly created resource in storage.
     */
  public function store(StoreAsignacionEquipoRequest $request)
{
    $asignacion = $this->asignacionesService->asignarProducto($request->all());

    return response()->json([
        'message' => 'Producto asignado exitosamente',
        'data' => $asignacion,
    ]);
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, Request $request)
    {
        $observaciones = $request->input('observaciones');
        $asignacion = $this->asignacionesService->desactivarAsignacion($id, $observaciones);

        return response()->json([
            'message' => 'Asignación desactivada correctamente',
            'data' => $asignacion,
        ]);
    }
}
