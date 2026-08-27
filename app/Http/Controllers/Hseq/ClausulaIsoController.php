<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreClausulaIsoRequest;
use App\Http\Requests\Hseq\UpdateClausulaIsoRequest;
use App\Services\Hseq\ClausulaIsoService;
use Illuminate\Http\Request;

class ClausulaIsoController extends Controller
{
    protected $clausulaIsoService;

    public function __construct(ClausulaIsoService $clausulaIsoService)
    {
        $this->clausulaIsoService = $clausulaIsoService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 100);
        $clausulasIso = $this->clausulaIsoService->all($search, $limit);
        return response()->json([
            'data' => $clausulasIso
        ]);
    }

    /**
     * Display a paginated listing of the active clauses for a given ISO norm.
     */
    public function porNorma(Request $request, string $norma_iso_id)
    {
        $perPage = $request->input('per_page', 15);
        $clausulasIso = $this->clausulaIsoService->porNorma($norma_iso_id, $perPage);
        return response()->json([
            'data' => $clausulasIso
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClausulaIsoRequest $request)
    {
        $data = $request->validated();
        $clausulaIso = $this->clausulaIsoService->create($data);
        return response()->json([
            'message' => 'Cláusula ISO creada exitosamente',
            'data' => $clausulaIso
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $clausulaIso = $this->clausulaIsoService->find($id);
        return response()->json([
            'data' => $clausulaIso
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClausulaIsoRequest $request, string $id)
    {
        $data = $request->validated();
        $clausulaIso = $this->clausulaIsoService->find($id);
        $updatedClausulaIso = $this->clausulaIsoService->update($clausulaIso, $data);
        return response()->json([
            'message' => 'Cláusula ISO actualizada exitosamente',
            'data' => $updatedClausulaIso
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $clausulaIso = $this->clausulaIsoService->find($id);
        $this->clausulaIsoService->delete($clausulaIso);
        return response()->json([
            'message' => 'Cláusula ISO eliminada exitosamente'
        ]);
    }
}
