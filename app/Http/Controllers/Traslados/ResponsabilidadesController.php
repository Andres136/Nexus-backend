<?php

namespace App\Http\Controllers\Traslados;

use App\Http\Controllers\Controller;
use App\Http\Requests\Traslados\AsignarResponsabilidadRequest;
use App\Http\Requests\Traslados\ResponsabilidadEstoreRequest;
use App\Models\Traslados\Responsabilidad;
use App\Services\Responsabilidades\ResponsablidadAsignacionService;
use Illuminate\Http\Request;

class ResponsabilidadesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct(
        protected ResponsablidadAsignacionService $service
    ) {}


    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ResponsabilidadEstoreRequest $request)
    {
         $data = new Responsabilidad($request->validated());
         $data->save();

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
