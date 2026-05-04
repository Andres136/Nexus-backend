<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\TipoContratoRequest;
use App\Services\Nomina\TipoContratoService;
use Illuminate\Http\JsonResponse;

class TipoContratoController extends Controller
{
    public function __construct(
        private TipoContratoService $tipoContratoService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->tipoContratoService->getAll();
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->tipoContratoService->getById($id);
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(TipoContratoRequest $request): JsonResponse
    {
        $data = $this->tipoContratoService->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Tipo de contrato creado correctamente.',
            'data'    => $data,
        ], 201);
    }

    public function update(TipoContratoRequest $request, int $id): JsonResponse
    {
        $data = $this->tipoContratoService->update($id, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Tipo de contrato actualizado correctamente.',
            'data'    => $data,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->tipoContratoService->delete($id);
        return response()->json([
            'success' => true,
            'message' => 'Tipo de contrato eliminado correctamente.',
        ]);
    }
}