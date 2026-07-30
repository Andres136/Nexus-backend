<?php

namespace App\Http\Controllers\comunicaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\comunicaciones\StoreRedSocialRequest;
use App\Services\comunicaciones\RedSocialService;
use Illuminate\Http\Request;

class RedSocialController extends Controller
{
    protected $redSocialService;

    public function __construct(RedSocialService $redSocialService)
    {
        $this->redSocialService = $redSocialService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 50);
        $redesSociales = $this->redSocialService->all($search, $limit);
        return response()->json($redesSociales);
    }

    public function store(StoreRedSocialRequest $request)
    {
        $data = $request->validated();
        $redSocial = $this->redSocialService->create($data);
        return response()->json([
            'message' => 'Red social creada exitosamente',
            'data' => $redSocial,
        ]);
    }

    public function show(string $id)
    {
        $redSocial = $this->redSocialService->find($id);
        return response()->json($redSocial);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->all();
        $redSocial = $this->redSocialService->find($id);
        $updated = $this->redSocialService->update($redSocial, $data);
        return response()->json([
            'message' => 'Red social actualizada exitosamente',
            'data' => $updated,
        ]);
    }

    public function destroy(string $id)
    {
        $redSocial = $this->redSocialService->find($id);
        $this->redSocialService->delete($redSocial);
        return response()->json([
            'message' => 'Red social eliminada exitosamente',
        ]);
    }
}
