<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreDescuentoRequest;
use App\Http\Requests\Nomina\UpdateDescuentoRequest;
use App\Services\Nomina\DescuentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DescuentoController extends Controller
{
    public function __construct(
        private readonly DescuentoService $descuentoService
    ) {}

    // GET /descuentos
    public function index(): JsonResponse
    {
        try {
            $descuentos = $this->descuentoService->getAll();

            return response()->json([
                'success' => true,
                'data'    => $descuentos,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // GET /descuentos/{uuid}
    public function show(string $uuid): JsonResponse       // ← int $id → string $uuid
    {
        try {
            $descuento = $this->descuentoService->getByUuid($uuid);   // ← getById → getByUuid

            return response()->json([
                'success' => true,
                'data'    => $descuento,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // POST /descuentos
    public function store(StoreDescuentoRequest $request): JsonResponse
    {
        try {
            $descuento = $this->descuentoService->store($request);

            return response()->json([
                'success' => true,
                'message' => 'Descuento creado exitosamente',
                'data'    => $descuento,
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // PUT /descuentos/{uuid}
    public function update(UpdateDescuentoRequest $request, string $uuid): JsonResponse  
    {
        try {
            $descuento = $this->descuentoService->update($request, $uuid); 

            return response()->json([
                'success' => true,
                'message' => 'Descuento actualizado exitosamente',
                'data'    => $descuento,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // DELETE /descuentos/{uuid}
    public function destroy(string $uuid): JsonResponse   
    {
        try {
            $this->descuentoService->destroy($uuid);       

            return response()->json([
                'success' => true,
                'message' => 'Descuento eliminado exitosamente',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(\Exception $e): JsonResponse
    {
        Log::error('Error en DescuentoController', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado',
        ], 500);
    }
}