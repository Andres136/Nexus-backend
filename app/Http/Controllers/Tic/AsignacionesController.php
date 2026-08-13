<?php

namespace App\Http\Controllers\Tic;

use App\Exports\GenericExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tic\StoreAsignacionEquipoRequest;
use App\Models\Tic\Asignaciones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    private function filtrosDesde(Request $request): array
    {
        return $request->only([
            'usuario_id',
            'empresa_id',
            'activo',
            'search',
            'per_page',
            'sede_id',
        ]);
    }

public function index(Request $request)
{
    $data = $this->asignacionesService->getAllAsignaciones($this->filtrosDesde($request));

    return response()->json($data);
}

    /**
     * GET /asignaciones/exportar
     * Exporta a Excel las asignaciones que cumplan los filtros aplicados en la vista.
     */
    public function exportar(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $registros = $this->asignacionesService->getAllAsignacionesParaExportar($this->filtrosDesde($request));

            if ($registros->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay asignaciones para exportar con los filtros seleccionados.',
                ], 422);
            }

            $filas = $registros->map(fn (Asignaciones $a) => [
                'ID' => $a->id,
                'Empresa' => $a->empresa?->nombre,
                'Usuario' => $a->usuario?->name,
                'Producto' => $a->producto?->name,
                'Recibe' => $a->usuarioRecibe?->name,
                'Sede' => $a->sede?->nombre,
                'Accesorios' => $a->accesorios,
                'Fecha asignación' => optional($a->created_at)->format('Y-m-d'),
                'Fecha devolución' => optional($a->fecha_devolucion)->format('Y-m-d'),
                'Estado' => $a->activo ? 'Activo' : 'Inactivo',
            ]);

            $headings = ['ID', 'Empresa', 'Usuario', 'Producto', 'Recibe', 'Sede', 'Accesorios', 'Fecha asignación', 'Fecha devolución', 'Estado'];
            $filename = 'asignaciones_' . now()->format('Y-m-d_His') . '.xlsx';

            return Excel::download(new GenericExport($filas, $headings), $filename);
        } catch (\Exception $e) {
            Log::error('Error al exportar asignaciones', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al exportar las asignaciones.'], 500);
        }
    }

    /**
     * PATCH /asignaciones/{id}/accesorios
     */
    public function actualizarAccesorios(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'accesorios' => 'nullable|string|max:1000',
        ]);

        try {
            $asignacion = $this->asignacionesService->actualizarAccesorios((int) $id, $validated['accesorios'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Accesorios actualizados.',
                'data' => $asignacion,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar accesorios de asignación', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al actualizar los accesorios.'], 500);
        }
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
