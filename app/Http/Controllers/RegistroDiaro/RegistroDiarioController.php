<?php

namespace App\Http\Controllers\RegistroDiaro;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroDiario\StoreRegistroDiarioRequest;
use App\Services\RegistroDiario\RegistroDiarioService;
use Illuminate\Http\Request;

class RegistroDiarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $registroDiarioService;

    public function __construct(RegistroDiarioService $registroDiarioService)
    {
        $this->registroDiarioService = $registroDiarioService;
    }

    public function index(int $anio)
    {
        $estadisticas = $this->registroDiarioService->estadisticasAnualesDepartamentos($anio);

        return response()->json($estadisticas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRegistroDiarioRequest $request)
    {
        $this->registroDiarioService->crearRegistroDiario($request->all());

        return response()->json([
            'message' => 'Registro creado exitosamente',
        ], 201);
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
    public function destroy(string $id)
    {
        //
    }
}
