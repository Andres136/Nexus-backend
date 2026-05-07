<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreContratacionRequest;
use App\Http\Requests\Nomina\UpdateContratacionRequest;
use App\Services\Nomina\ContratacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContratacionController extends Controller
{
    public function __construct(
        private ContratacionService $contratacionService
    ) {}

public function index(Request $request): JsonResponse
{
    $filters = [
        'search'       => $request->query('search'),
        'fecha_inicio' => $request->query('fecha_inicio'),
        'fecha_fin'    => $request->query('fecha_fin'),
        'per_page'     => $request->query('per_page', 10),
    ];

    $data = $this->contratacionService->getAll($filters);

    return response()->json([
        'success' => true,
        'data'    => $data,
    ]);
}

    public function show(string $uuid): JsonResponse
    {
        $data = $this->contratacionService->getByUuid($uuid);
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(StoreContratacionRequest $request): JsonResponse
    {
        $data = $this->contratacionService->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Contratación creada correctamente.',
            'data'    => $data,
        ], 201);
    }

    public function update(UpdateContratacionRequest $request, string $uuid): JsonResponse
    {
        $data = $this->contratacionService->update($uuid, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Contratación actualizada correctamente.',
            'data'    => $data,
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->contratacionService->delete($uuid);
        return response()->json([
            'success' => true,
            'message' => 'Contratación eliminada correctamente.',
        ]);
    }
}