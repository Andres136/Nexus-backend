<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreSeguridadSocialRequest;
use App\Http\Requests\Nomina\UpdateSeguridadSocialRequest;
use App\Services\Nomina\SeguridadSocialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeguridadSocialController extends Controller
{
    public function __construct(
        private SeguridadSocialService $seguridadSocialService
    ) {}

public function index(Request $request): JsonResponse
{
    $filters = [
        'search'       => $request->get('search'),
        'fecha_inicio' => $request->get('fecha_inicio'),
        'fecha_fin'    => $request->get('fecha_fin'),
        'tipo'         => $request->get('tipo'),
        'per_page'     => $request->get('per_page', 10),
    ];

    $data = $this->seguridadSocialService->getAll($filters);

    return response()->json([
        'success' => true,
        'data'    => $data,
    ]);
}

    public function show(string $uuid): JsonResponse
    {
        $data = $this->seguridadSocialService->getById($uuid);
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(StoreSeguridadSocialRequest $request): JsonResponse
    {
        $data = $this->seguridadSocialService->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Seguridad social creada correctamente.',
            'data'    => $data,
        ], 201);
    }

    public function update(UpdateSeguridadSocialRequest $request, string $uuid): JsonResponse
    {
        $data = $this->seguridadSocialService->update($uuid, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Seguridad social actualizada correctamente.',
            'data'    => $data,
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->seguridadSocialService->delete($uuid);
        return response()->json([
            'success' => true,
            'message' => 'Seguridad social eliminada correctamente.',
        ]);
    }
}
