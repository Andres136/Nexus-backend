<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\ContratacionRequest;
use App\Services\Nomina\ContratacionService;
use Illuminate\Http\JsonResponse;

class ContratacionController extends Controller
{
    public function __construct(
        private ContratacionService $contratacionService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->contratacionService->getAll();
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->contratacionService->getById($id);
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(ContratacionRequest $request): JsonResponse
    {
        $data = $this->contratacionService->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Contratación creada correctamente.',
            'data'    => $data,
        ], 201);
    }

    public function update(ContratacionRequest $request, int $id): JsonResponse
    {
        $data = $this->contratacionService->update($id, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Contratación actualizada correctamente.',
            'data'    => $data,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->contratacionService->delete($id);
        return response()->json([
            'success' => true,
            'message' => 'Contratación eliminada correctamente.',
        ]);
    }
}