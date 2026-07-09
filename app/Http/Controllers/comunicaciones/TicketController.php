<?php

namespace App\Http\Controllers\comunicaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\comunicaciones\CambiarEstadoTicketRequest;
use App\Http\Requests\comunicaciones\StoreTicketRequest;
use App\Http\Requests\comunicaciones\StoresHistorialRequest;
use App\Http\Requests\comunicaciones\UpdateTicketRequest;
use App\Services\comunicaciones\TiketsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private readonly TiketsService $tiketsService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tickets = $this->tiketsService->listTickets($request->only([
            'search',
            'estado',
            'prioridad',
            'user_solicitante_id',
            'user_asignado_id',
            'producto_id',
            'departamento_id',
            'fecha_desde',
            'fecha_hasta',
            'per_page',
        ]));

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->tiketsService->createTicket($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ticket creado correctamente.',
            'data' => $ticket,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->tiketsService->getTicket($id),
        ]);
    }

    public function resumenAsignados(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->tiketsService->getAssignedOpenSummary(),
        ]);
    }

    public function estadisticasParadas(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'producto_id' => ['nullable', 'integer', 'exists:products,id'],
            'departamento_id' => ['nullable', 'integer', 'exists:departamentos,id'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->tiketsService->getEquipmentDowntimeStats($filters),
        ]);
    }

    public function update(UpdateTicketRequest $request, int $id): JsonResponse
    {
        $ticket = $this->tiketsService->updateTicket($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ticket actualizado correctamente.',
            'data' => $ticket,
        ]);
    }

    public function cambiarEstado(CambiarEstadoTicketRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $ticket = $this->tiketsService->changeStatus(
            $id,
            $validated['estado'],
            $validated['comentario'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Estado del ticket actualizado correctamente.',
            'data' => $ticket,
        ]);
    }

    public function agregarHistorial(StoresHistorialRequest $request, int $id): JsonResponse
    {
        $historial = $this->tiketsService->addHistory($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Comentario agregado correctamente.',
            'data' => $historial,
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->tiketsService->deleteTicket($id);

        return response()->json([
            'success' => true,
            'message' => 'Ticket eliminado correctamente.',
        ]);
    }
}
