<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\SeguridadSocialRequest;
use App\Services\Nomina\SeguridadSocialService;
use Illuminate\Http\JsonResponse;

class SeguridadSocialController extends Controller
{
    public function __construct(
        private SeguridadSocialService $seguridadSocialService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->seguridadSocialService->getAll();
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->seguridadSocialService->getById($id);
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(SeguridadSocialRequest $request): JsonResponse
    {
        $data = $this->seguridadSocialService->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Seguridad social creada correctamente.',
            'data'    => $data,
        ], 201);
    }

    public function update(SeguridadSocialRequest $request, int $id): JsonResponse
    {
        $data = $this->seguridadSocialService->update($id, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Seguridad social actualizada correctamente.',
            'data'    => $data,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->seguridadSocialService->delete($id);
        return response()->json([
            'success' => true,
            'message' => 'Seguridad social eliminada correctamente.',
        ]);
    }
}