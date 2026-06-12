<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreNominaParametroLaboralRequest;
use App\Services\Nomina\NominaParametroLaboralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NominaParametroLaboralController extends Controller
{
    public function __construct(
        private readonly NominaParametroLaboralService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getAll($request->only(['anio', 'activo', 'per_page'])),
        ]);
    }

    public function vigente(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->vigente($request->query('fecha')),
        ]);
    }

    public function store(StoreNominaParametroLaboralRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Parámetro laboral creado correctamente.',
            'data' => $this->service->store($request->validated()),
        ], 201);
    }

    public function update(StoreNominaParametroLaboralRequest $request, string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Parámetro laboral actualizado correctamente.',
            'data' => $this->service->update($uuid, $request->validated()),
        ]);
    }
}
