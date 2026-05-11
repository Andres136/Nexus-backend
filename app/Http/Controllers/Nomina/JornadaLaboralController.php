<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreJornadaLaboralRequest;
use App\Http\Requests\Nomina\UpdateJornadaLaboralRequest;
use App\Services\Nomina\JornadaLaboralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class JornadaLaboralController extends Controller
{
    public function __construct(
        private readonly JornadaLaboralService $jornadaLaboralService
    ) {}

    // GET /jornada-laborals
    public function index(): JsonResponse
    {
        try {
            $jornadas = $this->jornadaLaboralService->getAll();

            return response()->json([
                'success' => true,
                'data'    => $jornadas,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // GET /jornada-laborals/{uuid}
    public function show(string $uuid): JsonResponse          // ← int $id → string $uuid
    {
        try {
            $jornada = $this->jornadaLaboralService->getByUuid($uuid);  // ← getById → getByUuid

            return response()->json([
                'success' => true,
                'data'    => $jornada,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // POST /jornada-laborals
    public function store(StoreJornadaLaboralRequest $request): JsonResponse
    {
        try {
            $jornada = $this->jornadaLaboralService->store($request);

            return response()->json([
                'success' => true,
                'message' => 'Jornada laboral creada exitosamente',
                'data'    => $jornada,
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // PUT /jornada-laborals/{uuid}
    public function update(UpdateJornadaLaboralRequest $request, string $uuid): JsonResponse  
    {
        try {
            $jornada = $this->jornadaLaboralService->update($request, $uuid);  

            return response()->json([
                'success' => true,
                'message' => 'Jornada laboral actualizada exitosamente',
                'data'    => $jornada,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // DELETE /jornada-laborals/{uuid}
    public function destroy(string $uuid): JsonResponse       
    {
        try {
            $this->jornadaLaboralService->destroy($uuid);     

            return response()->json([
                'success' => true,
                'message' => 'Jornada laboral eliminada exitosamente',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(\Exception $e): JsonResponse
    {
        Log::error('Error en JornadaLaboralController', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado',
        ], 500);
    }
}