<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreHorarioLaboralRequest;
use App\Http\Requests\Nomina\UpdateHorarioLaboralRequest;
use App\Services\Nomina\HorarioLaboralService;
use Illuminate\Http\JsonResponse;

class HorarioLaboralController extends Controller
{
    public function __construct(
        private HorarioLaboralService $horarioLaboralService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->horarioLaboralService->getAll();
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->horarioLaboralService->getById($id);
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(StoreHorarioLaboralRequest $request): JsonResponse
    {
        $data = $this->horarioLaboralService->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Horario laboral creado correctamente.',
            'data'    => $data,
        ], 201);
    }

    public function update(UpdateHorarioLaboralRequest $request, int $id): JsonResponse
    {
        $data = $this->horarioLaboralService->update($id, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Horario laboral actualizado correctamente.',
            'data'    => $data,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->horarioLaboralService->delete($id);
        return response()->json([
            'success' => true,
            'message' => 'Horario laboral eliminado correctamente.',
        ]);
    }
}
