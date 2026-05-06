<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreTipoRegistroRequest;
use App\Http\Requests\Nomina\UpdateTipoRegistroRequest;
use App\Services\Nomina\TipoRegistroService;
use Illuminate\Http\JsonResponse;

class TipoRegistroController extends Controller
{
    public function __construct(
        private TipoRegistroService $tipoRegistroService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->tipoRegistroService->getAll();
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->tipoRegistroService->getById($id);
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(StoreTipoRegistroRequest $request): JsonResponse
    {
        $data = $this->tipoRegistroService->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Tipo de registro creado correctamente.',
            'data'    => $data,
        ], 201);
    }

    public function update(UpdateTipoRegistroRequest $request, int $id): JsonResponse
    {
        $data = $this->tipoRegistroService->update($id, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Tipo de registro actualizado correctamente.',
            'data'    => $data,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->tipoRegistroService->delete($id);
        return response()->json([
            'success' => true,
            'message' => 'Tipo de registro eliminado correctamente.',
        ]);
    }
}
