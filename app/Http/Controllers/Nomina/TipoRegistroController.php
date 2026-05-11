<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreTipoRegistroRequest;
use App\Http\Requests\Nomina\UpdateTipoRegistroRequest;
use App\Services\Nomina\TipoRegistroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TipoRegistroController extends Controller
{
    public function __construct(
        private readonly TipoRegistroService $tipoRegistroService  
        
    ) {}

    public function index(): JsonResponse
    {
        try {
            $data = $this->tipoRegistroService->getAll();

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function show(string $uuid): JsonResponse               
    
    {
        try {
            $data = $this->tipoRegistroService->getByUuid($uuid);  
            
            

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function store(StoreTipoRegistroRequest $request): JsonResponse
    {
        try {
            $data = $this->tipoRegistroService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tipo de registro creado correctamente.',
                'data'    => $data,
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function update(UpdateTipoRegistroRequest $request, string $uuid): JsonResponse  
    {
        try {
            $data = $this->tipoRegistroService->update($uuid, $request->validated()); 

            return response()->json([
                'success' => true,
                'message' => 'Tipo de registro actualizado correctamente.',
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function destroy(string $uuid): JsonResponse            
    {
        try {
            $this->tipoRegistroService->delete($uuid);             

            return response()->json([
                'success' => true,
                'message' => 'Tipo de registro eliminado correctamente.',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(\Exception $e): JsonResponse    // ← método nuevo
    {
        Log::error('Error en TipoRegistroController', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado',
        ], 500);
    }
}
