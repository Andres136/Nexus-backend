<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreTipoContratoRequest;
use App\Http\Requests\Nomina\UpdateTipoContratoRequest;
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

    public function show(string $uuid): JsonResponse
    {
        $data = $this->tipoContratoService->getById($uuid);
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(StoreTipoContratoRequest $request): JsonResponse
    {
        $data = $this->tipoContratoService->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Tipo de contrato creado correctamente.',
            'data'    => $data,
        ], 201);
    }

    public function update(UpdateTipoContratoRequest $request, string $uuid): JsonResponse
    {
        $data = $this->tipoContratoService->update($uuid, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Tipo de contrato actualizado correctamente.',
            'data'    => $data,
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->tipoContratoService->delete($uuid);
        return response()->json([
            'success' => true,
            'message' => 'Tipo de contrato eliminado correctamente.',
        ]);
    }
}