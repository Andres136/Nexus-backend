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
    // Inyección de dependencias en el constructor
    // Laravel instancia TransacionalRegistroService automáticamente — no necesitas "new"
    public function __construct(
        private readonly TransacionalRegistroService $transacionalRegistroService
    ) {}

    // index() = responde GET /transacional-registros
    // Retorna todas las marcaciones con sus relaciones
    public function index(): JsonResponse
    {
        try {
            $data = $this->transacionalRegistroService->getAll();
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // show() = responde GET /transacional-registros/{id}
    // {id} llega como parámetro de ruta — Laravel lo pasa como $id
    public function show(int $id): JsonResponse
    {
        try {
            $data = $this->transacionalRegistroService->getById($id);
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // byUser() = responde GET /transacional-registros/user/{userId}
    // Ruta extra para ver el historial de marcaciones de un empleado
    public function byUser(int $userId): JsonResponse
    {
        try {
            $data = $this->transacionalRegistroService->getByUser($userId);
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // store() = responde POST /transacional-registros
    // StoreTransacionalRegistroRequest valida automáticamente antes de entrar aquí
    public function store(StoreTransacionalRegistroRequest $request): JsonResponse
    {
        try {
            $data = $this->transacionalRegistroService->store($request);
            return response()->json([
                'success' => true,
                'message' => 'Marcación registrada correctamente.',
                'data'    => $data,
            ], 201); // 201 Created = recurso creado exitosamente
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // update() = responde PUT/PATCH /transacional-registros/{id}
    public function update(UpdateTransacionalRegistroRequest $request, int $id): JsonResponse
    {
        try {
            $data = $this->transacionalRegistroService->update($request, $id);
            return response()->json([
                'success' => true,
                'message' => 'Marcación actualizada correctamente.',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // destroy() = responde DELETE /transacional-registros/{id}
    // Hace soft delete — no borra físicamente
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->transacionalRegistroService->delete($id);
            return response()->json([
                'success' => true,
                'message' => 'Marcación eliminada correctamente.',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // errorResponse() = método privado centralizado para manejar errores
    // Evita repetir el mismo bloque catch en cada método
    private function errorResponse(\Exception $e): JsonResponse
    {
        Log::error('Error en TransacionalRegistroController', [
            'message' => $e->getMessage(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado.',
        ], 500); // 500 = Internal Server Error
    }
}
