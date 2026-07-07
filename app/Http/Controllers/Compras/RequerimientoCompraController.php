<?php

namespace App\Http\Controllers\Compras;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compras\AnalizarRequerimientoCompraRequest;
use App\Http\Requests\Compras\AprobarRequerimientoCompraRequest;
use App\Http\Requests\Compras\CancelarRequerimientoCompraRequest;
use App\Http\Requests\Compras\GenerarOrdenCompraDesdeRequerimientoRequest;
use App\Http\Requests\Compras\RechazarRequerimientoCompraRequest;
use App\Http\Requests\Compras\StoreRequerimientoCompraRequest;
use App\Http\Requests\Compras\UpdateRequerimientoCompraRequest;
use App\Services\Compras\RequerimientoCompraService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RequerimientoCompraController extends Controller
{
    public function __construct(private readonly RequerimientoCompraService $service) {}

    public function index(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->listar($request->only([
                    'search',
                    'estado',
                    'sede_id',
                    'bodega_id',
                    'user_id',
                    'orden_compra_id',
                    'fecha_inicio',
                    'fecha_fin',
                    'per_page',
                    'solo_mios',
                ]), $request->user()),
                'meta' => [
                    'puede_gestionar' => $this->service->puedeGestionarCompras($request->user()),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al listar requerimientos de compra', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al listar requerimientos.'], 500);
        }
    }

    public function store(StoreRequerimientoCompraRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Requerimiento de compra creado correctamente.',
                'data' => $this->service->crear($request->validated(), $request->user()),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error al crear requerimiento de compra', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al crear el requerimiento.'], 500);
        }
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->obtener($uuid, $request->user()),
            'meta' => [
                'puede_gestionar' => $this->service->puedeGestionarCompras($request->user()),
            ],
        ]);
    }

    public function bodegasDisponibles(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->bodegasDisponibles($request->user()),
        ]);
    }

    public function analizar(AnalizarRequerimientoCompraRequest $request, string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Requerimiento marcado en análisis.',
            'data' => $this->service->marcarEnAnalisis($uuid, $request->validated(), $request->user()),
        ]);
    }

    public function update(UpdateRequerimientoCompraRequest $request, string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Requerimiento actualizado correctamente.',
            'data' => $this->service->actualizar($uuid, $request->validated(), $request->user()),
        ]);
    }

    public function aprobar(AprobarRequerimientoCompraRequest $request, string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Requerimiento aprobado para compra.',
            'data' => $this->service->aprobar($uuid, $request->validated(), $request->user()),
        ]);
    }

    public function rechazar(RechazarRequerimientoCompraRequest $request, string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Requerimiento rechazado.',
            'data' => $this->service->rechazar($uuid, $request->validated(), $request->user()),
        ]);
    }

    public function cancelar(CancelarRequerimientoCompraRequest $request, string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Requerimiento cancelado.',
            'data' => $this->service->cancelar($uuid, $request->validated(), $request->user()),
        ]);
    }

    public function generarOrdenCompra(GenerarOrdenCompraDesdeRequerimientoRequest $request, string $uuid): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Orden de compra generada desde requerimiento.',
            'data' => $this->service->generarOrdenCompra($uuid, $request->validated(), $request->user()),
        ]);
    }

    public function pdf(Request $request, string $uuid)
    {
        $requerimiento = $this->service->obtener($uuid, $request->user());

        return Pdf::loadView('pdf.requerimiento_compra', [
            'requerimiento' => $requerimiento,
        ])->download("requerimiento_compra_{$requerimiento->codigo}.pdf");
    }
}
