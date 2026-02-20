<?php

namespace App\Http\Controllers\RegistroDiario;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroDiario\StoreVerificacionDiariaRequest;
use App\Services\RegistroDiario\RegistroDarioService;
use App\Services\RegistroDiario\RegistroDiarioService;
use App\Services\RegistroDiario\VerificacionDiariaService;
use Illuminate\Http\Request;

class VerificacionDiariaControllerr extends Controller
{


    protected $verificacionDiariaService;
    protected $registroDiarioService;
    /**
     * Display a listing of the resource.
     */

    public function __construct(VerificacionDiariaService $verificacionDiariaService, RegistroDiarioService $registroDiarioService)
    {
        $this->verificacionDiariaService = $verificacionDiariaService;
        $this->registroDiarioService = $registroDiarioService;
    }
    public function index(int $anio)
    {
        $estadisticas = $this->registroDiarioService->estadisticasAnuales($anio);

        return response()->json($estadisticas);
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVerificacionDiariaRequest $request)
    {
        $data = $request->validated();
        $verificacionDiaria = $this->verificacionDiariaService->create($data);

        return response()->json([
            'message' => 'Verificación diaria creada exitosamente.',
            'data' => $verificacionDiaria
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $verificacionDiaria = $this->registroDiarioService->getByDepartamento($id);

        if (!$verificacionDiaria) {
            return response()->json(['message' => 'Verificación diaria no encontrada.'], 404);
        }

        return response()->json($verificacionDiaria);
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
