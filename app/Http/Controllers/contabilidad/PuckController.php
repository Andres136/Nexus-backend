<?php

namespace App\Http\Controllers\contabilidad;

use App\Http\Controllers\Controller;
use App\Http\Requests\contabilidad\StorePuckRequest;
use App\Services\contabilidad\PuckService;
use Illuminate\Http\Request;

class PuckController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $puckService;
    public function __construct(PuckService $puckService)
    {
        $this->puckService = $puckService;
    }
    public function index()
    {
        $pucks = $this->puckService->listar();

        return response()->json([
            'data' => $pucks
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePuckRequest $request)
    {
        $data = $request->validated();
       
        $result = $this->puckService->create($data);
        return response()->json([
            'message' => 'Puck creado correctamente',
            'data' => $result
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = $this->puckService->getById($id);
        if ($data) {
            return response()->json([
                'message' => 'Puck encontrado',
                'data' => $data
            ]);
        } else {
            return response()->json([
                'message' => 'Puck no encontrado'
            ], 404);

        }
        return response()->json([
            'message' => 'Puck encontrado',
            'data' => $data
        ]);   
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->all();
        $result = $this->puckService->update($data, $id);
        return response()->json([
            'message' => 'Puck actualizado correctamente',
            'data' => $result
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $result = $this->puckService->delete($id);
        if ($result) {
            return response()->json([
                'message' => 'Puck eliminado correctamente'
            ]);
        } else {
            return response()->json([
                'message' => 'Puck no encontrado'
            ], 404);
        }
    }
}
