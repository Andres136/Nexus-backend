<?php

namespace App\Http\Controllers\Traslados;

use App\Http\Controllers\Controller;
use App\Http\Requests\Traslados\AsignarResponsabilidadRequest;
use App\Http\Requests\Traslados\ResponsabilidadEstoreRequest;
use App\Models\Traslados\Responsabilidad;
use App\Services\Responsabilidades\ResponsablidadAsignacionService;
use App\Services\Traslados\ResponsabilidadesService;
use Illuminate\Http\Request;

class ResponsabilidadesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct(
        protected ResponsablidadAsignacionService $service,
        protected ResponsabilidadesService $responsabilidadesService
    ) {}


    public function index(Request $request)
    {
        $responsabilidades = $this->responsabilidadesService->listar(
            $request->only([
                'search',
                'activo',
                'order_by',
                'order',
                'per_page'
            ])
        );
        return response()->json($responsabilidades);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ResponsabilidadEstoreRequest $request)
    {
        $data = $this->responsabilidadesService->crear(
            $request->validated()
        );

         return response()->json([
            'message' => 'Responsabilidad creada exitosamente',
            'data' => $data
         ], 201);
    }

   /**
    * Asignar una responsabilidad a un usuario.
    */
   public function asignarResponsabilidad(
        AsignarResponsabilidadRequest $request,
        string $id
    ) {
        $responsabilidad = $this->service->asignar(
            (int) $id,
            $request->validated()
        );

        return response()->json([
            'message' => 'Responsabilidad asignada exitosamente',
            'data'    => $responsabilidad,
        ], 200);
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
