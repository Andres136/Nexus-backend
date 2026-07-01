<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreContratacionCambioRequest;
use App\Services\Nomina\ContratacionCambioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LogicException;

class ContratacionCambioController extends Controller
{
    public function __construct(
        private ContratacionCambioService $contratacionCambioService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->contratacionCambioService->getAll([
            'search' => $request->query('search'),
            'contratacion_id' => $request->query('contratacion_id'),
            'user_id' => $request->query('user_id'),
            'tipo_cambio' => $request->query('tipo_cambio'),
            'per_page' => $request->query('per_page', 10),
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show(string $uuid): JsonResponse
    {
        $data = $this->contratacionCambioService->getByUuid($uuid);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(StoreContratacionCambioRequest $request): JsonResponse
    {
        try {
            $data = $this->contratacionCambioService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Cambio contractual registrado correctamente.',
                'data' => $data,
            ], 201);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Error al registrar cambio contractual', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al registrar el cambio contractual.'], 500);
        }
    }
}
