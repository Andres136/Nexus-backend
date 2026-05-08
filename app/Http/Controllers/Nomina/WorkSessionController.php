<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreWorkSessionRequest;
use App\Http\Requests\Nomina\UpdateWorkSessionRequest;
use App\Services\Nomina\WorkSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WorkSessionController extends Controller
{
    public function __construct(
        private readonly WorkSessionService $workSessionService
    ) {}

    // GET /work-sessions
    public function index(): JsonResponse
    {
        try {
            $sessions = $this->workSessionService->getAll();

            return response()->json([
                'success' => true,
                'data'    => $sessions,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // GET /work-sessions/{id}
    public function show(int $id): JsonResponse
    {
        try {
            $session = $this->workSessionService->getById($id);

            return response()->json([
                'success' => true,
                'data'    => $session,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // POST /work-sessions
    public function store(StoreWorkSessionRequest $request): JsonResponse
    {
        try {
            $session = $this->workSessionService->store($request);

            return response()->json([
                'success' => true,
                'message' => 'Sesión de trabajo creada exitosamente',
                'data'    => $session,
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // PUT /work-sessions/{id}
    public function update(UpdateWorkSessionRequest $request, int $id): JsonResponse
    {
        try {
            $session = $this->workSessionService->update($request, $id);

            return response()->json([
                'success' => true,
                'message' => 'Sesión de trabajo actualizada exitosamente',
                'data'    => $session,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // DELETE /work-sessions/{id}
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->workSessionService->destroy($id);

            return response()->json([
                'success' => true,
                'message' => 'Sesión de trabajo eliminada exitosamente',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(\Exception $e): JsonResponse
    {
        Log::error('Error en WorkSessionController', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado',
        ], 500);
    }
}
