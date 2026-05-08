<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreNominaRequest;
use App\Http\Requests\Nomina\UpdateNominaRequest;
use App\Services\Nomina\NominaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NominaController extends Controller
{
    public function __construct(
        private readonly NominaService $nominaService
    ) {}

    // GET /nomina
    public function index(): JsonResponse
    {
        try {
            $nominas = $this->nominaService->getAll();

            return response()->json([
                'success' => true,
                'data'    => $nominas,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // GET /nomina/{id}
    public function show(int $id): JsonResponse
    {
        try {
            $nomina = $this->nominaService->getById($id);

            return response()->json([
                'success' => true,
                'data'    => $nomina,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // POST /nomina
    public function store(StoreNominaRequest $request): JsonResponse
    {
        try {
            $nomina = $this->nominaService->store($request);

            return response()->json([
                'success' => true,
                'message' => 'Nómina creada exitosamente',
                'data'    => $nomina,
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // PUT /nomina/{id}
    public function update(UpdateNominaRequest $request, int $id): JsonResponse
    {
        try {
            $nomina = $this->nominaService->update($request, $id);

            return response()->json([
                'success' => true,
                'message' => 'Nómina actualizada exitosamente',
                'data'    => $nomina,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // DELETE /nomina/{id}
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->nominaService->destroy($id);

            return response()->json([
                'success' => true,
                'message' => 'Nómina eliminada exitosamente',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(\Exception $e): JsonResponse
    {
        Log::error('Error en NominaController', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado',
        ], 500);
    }
}