<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreNormaIsoRequest;
use App\Http\Requests\Hseq\UpdateNormaIsoRequest;
use App\Services\Hseq\NormaIsoService;
use Illuminate\Http\Request;

class NormaIsoController extends Controller
{
    protected $normaIsoService;

    public function __construct(NormaIsoService $normaIsoService)
    {
        $this->normaIsoService = $normaIsoService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 100);
        $normasIso = $this->normaIsoService->all($search, $limit);
        return response()->json([
            'data' => $normasIso
        ]);
    }

    /**
     * Display a paginated listing of the resource (pantalla admin).
     */
    public function paginado(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);
        $normasIso = $this->normaIsoService->paginado($search, $perPage);
        return response()->json([
            'data' => $normasIso
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNormaIsoRequest $request)
    {
        $data = $request->validated();
        $normaIso = $this->normaIsoService->create($data);
        return response()->json([
            'message' => 'Norma ISO creada exitosamente',
            'data' => $normaIso
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $normaIso = $this->normaIsoService->find($id);
        return response()->json([
            'data' => $normaIso
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNormaIsoRequest $request, string $id)
    {
        $data = $request->validated();
        $normaIso = $this->normaIsoService->find($id);
        $updatedNormaIso = $this->normaIsoService->update($normaIso, $data);
        return response()->json([
            'message' => 'Norma ISO actualizada exitosamente',
            'data' => $updatedNormaIso
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $normaIso = $this->normaIsoService->find($id);
        $this->normaIsoService->delete($normaIso);
        return response()->json([
            'message' => 'Norma ISO eliminada exitosamente'
        ]);
    }
}
