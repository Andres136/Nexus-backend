<?php

namespace App\Http\Controllers\comunicaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\comunicaciones\CertificacionRequest;
use App\Services\comunicaciones\CertificacionService;
use Illuminate\Http\Request;

class CertificacionController extends Controller
{
    protected $certificacionService;

    public function __construct(CertificacionService $certificacionService)
    {
        $this->certificacionService = $certificacionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 50);
        $activo = $request->input('activo');
        $certificaciones = $this->certificacionService->all($search, $limit, $activo);
        return response()->json($certificaciones);
    }

    /**
     * Listado público: solo certificaciones activas.
     */
    public function publicas(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 50);
        $certificaciones = $this->certificacionService->all($search, $limit, true);
        return response()->json($certificaciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CertificacionRequest $request)
    {
        $certificacion = $this->certificacionService->create($request->validated());
        return response()->json([
            'message' => 'Certificación creada exitosamente',
            'data' => $certificacion,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $certificacion = $this->certificacionService->find($id);
        return response()->json($certificacion);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CertificacionRequest $request, string $id)
    {
        $certificacion = $this->certificacionService->find($id);
        $updated = $this->certificacionService->update($certificacion, $request->validated());
        return response()->json([
            'message' => 'Certificación actualizada exitosamente',
            'data' => $updated,
        ]);
    }

    /**
     * Activar / desactivar la certificación.
     */
    public function toggleActivo(string $id)
    {
        $certificacion = $this->certificacionService->find($id);
        $updated = $this->certificacionService->toggleActivo($certificacion);
        return response()->json([
            'message' => $updated->activo
                ? 'Certificación activada'
                : 'Certificación desactivada',
            'data' => $updated,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $certificacion = $this->certificacionService->find($id);
        $this->certificacionService->delete($certificacion);
        return response()->json([
            'message' => 'Certificación eliminada exitosamente',
        ]);
    }
}
