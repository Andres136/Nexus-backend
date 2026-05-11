<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreTransacionalRegistroRequest;
use App\Http\Requests\Nomina\UpdateTransacionalRegistroRequest;
use App\Services\Nomina\TransacionalRegistroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TransacionalRegistroController extends Controller
{
    public function __construct(
        private readonly TransacionalRegistroService $transacionalRegistroService
    ) {}

    // GET /transacional-registros
    public function index(): JsonResponse
    {
        try {
            $data = $this->transacionalRegistroService->getAll();

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // GET /transacional-registros/{uuid}
    public function show(string $uuid): JsonResponse               
    {
        try {
            $data = $this->transacionalRegistroService->getByUuid($uuid);  

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // GET /transacional-registros/user/{userId}
    public function byUser(int $userId): JsonResponse              
    {
        try {
            $data = $this->transacionalRegistroService->getByUser($userId);

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // POST /transacional-registros
    public function store(StoreTransacionalRegistroRequest $request): JsonResponse
    {
        try {
            $data = $this->transacionalRegistroService->store($request);

            return response()->json([
                'success' => true,
                'message' => 'Marcación registrada correctamente.',
                'data'    => $data,
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // PUT /transacional-registros/{uuid}
    public function update(UpdateTransacionalRegistroRequest $request, string $uuid): JsonResponse  
    {
        try {
            $data = $this->transacionalRegistroService->update($request, $uuid);  

            return response()->json([
                'success' => true,
                'message' => 'Marcación actualizada correctamente.',
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // DELETE /transacional-registros/{uuid}
    public function destroy(string $uuid): JsonResponse           
    {
        try {
            $this->transacionalRegistroService->delete($uuid);     

            return response()->json([
                'success' => true,
                'message' => 'Marcación eliminada correctamente.',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(\Exception $e): JsonResponse
    {
        Log::error('Error en TransacionalRegistroController', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado',
        ], 500);
    }
}