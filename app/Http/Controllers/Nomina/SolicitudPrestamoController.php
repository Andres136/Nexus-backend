<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\GestionSolicitudPrestamoRequest;
use App\Http\Requests\Nomina\StoreSolicitudPrestamoRequest;
use App\Services\Nomina\DescuentoService;
use App\Services\Nomina\SolicitudPrestamoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use LogicException;

class SolicitudPrestamoController extends Controller
{
    public function __construct(
        private readonly SolicitudPrestamoService $service,
        private readonly DescuentoService $descuentoService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->getAll($request->only(['search', 'user_id', 'status', 'per_page'])),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al listar solicitudes de prestamos', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener solicitudes de prestamos.'], 500);
        }
    }

    public function portalIndex(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->getAll([
                    'user_id' => Auth::id(),
                    'status' => $request->query('status'),
                    'per_page' => $request->query('per_page', 15),
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('Portal: error al listar solicitudes de prestamos', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener tus solicitudes de prestamos.'], 500);
        }
    }

    public function store(StoreSolicitudPrestamoRequest $request): JsonResponse
    {
        try {
            $solicitud = $this->service->store($request->validated(), Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de prestamo registrada.',
                'data' => $solicitud,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear solicitud de prestamo', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al registrar la solicitud.'], 500);
        }
    }

    public function aprobar(GestionSolicitudPrestamoRequest $request, string $uuid): JsonResponse
    {
        try {
            $solicitud = $this->service->aprobar($uuid, $request->validated(), $this->descuentoService);

            return response()->json([
                'success' => true,
                'message' => 'Prestamo aprobado y descuento creado.',
                'data' => $solicitud,
            ]);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al aprobar solicitud de prestamo', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al aprobar la solicitud.'], 500);
        }
    }

    public function rechazar(Request $request, string $uuid): JsonResponse
    {
        try {
            $validated = $request->validate([
                'observacion_nomina' => 'nullable|string|max:1000',
            ]);

            $solicitud = $this->service->rechazar($uuid, $validated['observacion_nomina'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de prestamo rechazada.',
                'data' => $solicitud,
            ]);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al rechazar solicitud de prestamo', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al rechazar la solicitud.'], 500);
        }
    }
}
