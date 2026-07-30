<?php

namespace App\Http\Controllers\comunicaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\comunicaciones\StoreTipoPostRequest;
use App\Services\comunicaciones\TipoPostService;
use Illuminate\Http\Request;

class TipoPostController extends Controller
{
    protected $tipoPostService;

    public function __construct(TipoPostService $tipoPostService)
    {
        $this->tipoPostService = $tipoPostService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 50);
        $tiposPost = $this->tipoPostService->all($search, $limit);
        return response()->json($tiposPost);
    }

    public function store(StoreTipoPostRequest $request)
    {
        $data = $request->validated();
        $tipoPost = $this->tipoPostService->create($data);
        return response()->json([
            'message' => 'Tipo de post creado exitosamente',
            'data' => $tipoPost,
        ]);
    }

    public function show(string $id)
    {
        $tipoPost = $this->tipoPostService->find($id);
        return response()->json($tipoPost);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->all();
        $tipoPost = $this->tipoPostService->find($id);
        $updated = $this->tipoPostService->update($tipoPost, $data);
        return response()->json([
            'message' => 'Tipo de post actualizado exitosamente',
            'data' => $updated,
        ]);
    }

    public function destroy(string $id)
    {
        $tipoPost = $this->tipoPostService->find($id);
        $this->tipoPostService->delete($tipoPost);
        return response()->json([
            'message' => 'Tipo de post eliminado exitosamente',
        ]);
    }
}
