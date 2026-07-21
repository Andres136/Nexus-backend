<?php

namespace App\Http\Controllers\Traslados;

use App\Http\Controllers\Controller;
use App\Http\Requests\Traslados\AsignarResponsabilidadRequest;
use App\Http\Requests\Traslados\ResponsabilidadEstoreRequest;
use App\Services\Responsabilidades\ResponsabilidadesService;
use App\Services\Responsabilidades\ResponsablidadAsignacionService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

//Mostrar Responsabilidades asignadas
public function mostrarResponsabilidadesAsignadas()
{


    
    $responsabilidades = $this->service->listarResponsabilidades(
        request('per_page', 15),
        [
            'sede_id'   => request('sede_id'),
            'bodega_id'=> request('bodega_id'),
            'activo'    => true,
        ]
    );

    return response()->json($responsabilidades);
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
        $data = $this->responsabilidadesService->actualizar(
            $id,
            $request->all()
        );

        if (!$data) {
            return response()->json([
                'message' => 'Error al actualizar la responsabilidad',
            ], 400);
        }

        return response()->json([
            'message' => 'Responsabilidad actualizada exitosamente',
            'data'    => $data,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deleted = $this->responsabilidadesService->eliminar($id);

        if (!$deleted) {
            return response()->json([
                'message' => 'Error al eliminar la responsabilidad',
            ], 400);
        }

        return response()->json([
            'message' => 'Responsabilidad eliminada exitosamente',
        ], 200);
    }


public function actualizarAsignacion($pivotId, Request $request)
{
    $pivotId = (int) $pivotId;

    $data = $request->validate([
        'sede_id'   => 'required|exists:sedes,id',
        'bodega_id'=> 'required|exists:bodegas,id',
        'activo'    => 'required|boolean',
    ]);

    // update() de MySQL/Laravel devuelve filas realmente modificadas, no filas
    // que hicieron match: si los valores nuevos son iguales a los actuales
    // devuelve 0 aunque el registro exista y la operación sea correcta. Por
    // eso se verifica existencia aparte en vez de usar el resultado de update().
    if (!DB::table('responsabilidades_user')->where('id', $pivotId)->exists()) {
        return response()->json([
            'message' => 'No se pudo actualizar la asignación'
        ], 409);
    }

    DB::table('responsabilidades_user')
        ->where('id', $pivotId)
        ->update([
            'sede_id'    => $data['sede_id'],
            'bodega_id' => $data['bodega_id'],
            'activo'     => $data['activo'],
            'updated_at'=> now(),
        ]);

    return response()->json([
        'message' => 'Asignación actualizada correctamente'
    ]);
}



public function desactivarAsignacion($pivotId)
{try {
        $pivotId = (int) $pivotId;

        // Igual que en actualizarAsignacion: no usar el conteo de update() como
        // indicador de éxito, porque si 'activo' ya era false, MySQL reporta 0
        // filas modificadas aunque el registro exista.
        if (!DB::table('responsabilidades_user')->where('id', $pivotId)->exists()) {
            return response()->json([
                'message' => 'No se pudo desactivar la asignación'
            ], 409);
        }

        DB::table('responsabilidades_user')
            ->where('id', $pivotId)
            ->update([
                'activo'     => false,
                'updated_at'=> now(),
            ]);

        return response()->json([
            'message' => 'Asignación desactivada correctamente'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al desactivar la asignación',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
    




}
