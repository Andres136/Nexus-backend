<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreValorRequest;
use App\Http\Requests\Nomina\UpdateValorRequest;
use App\Services\Nomina\ValorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ValorController extends Controller
{
    public function __construct(
        private readonly ValorService $valorService
    ) {}

    // GET /valores
    public function index(): JsonResponse
    {
        try {
            $valores = $this->valorService->getAll();

            return response()->json([
                'success' => true,
                'data'    => $valores,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // GET /valores/{uuid}
    public function show(string $uuid): JsonResponse               
    {
        try {
            $valor = $this->valorService->getByUuid($uuid);        

            return response()->json([
                'success' => true,
                'data'    => $valor,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // POST /valores
    public function store(StoreValorRequest $request): JsonResponse
    {
        try {
            $valor = $this->valorService->store($request);

            return response()->json([
                'success' => true,
                'message' => 'Valores creados exitosamente',
                'data'    => $valor,
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // PUT /valores/{uuid}
    public function update(UpdateValorRequest $request, string $uuid): JsonResponse  
    {
        try {
            $valor = $this->valorService->update($request, $uuid);  

            return response()->json([
                'success' => true,
                'message' => 'Valores actualizados exitosamente',
                'data'    => $valor,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // DELETE /valores/{uuid}
    public function destroy(string $uuid): JsonResponse            
    {
        try {
            $this->valorService->destroy($uuid);                 

            return response()->json([
                'success' => true,
                'message' => 'Valores eliminados exitosamente',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(\Exception $e): JsonResponse
    {
        Log::error('Error en ValorController', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado',
        ], 500);
    }
}