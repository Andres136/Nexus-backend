<?php

namespace App\Http\Controllers;

use App\Http\Requests\TareaRequest;
use App\Services\TareaService;
use App\Services\TareaVencidaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    public function __construct(
        private TareaService $tareaService,
        private TareaVencidaService $tareaVencidaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->tareaVencidaService->notificarTareasVencidas();

        $tareas = $this->tareaService->listar($request, $request->user());

        return response()->json($tareas);
    }

    public function store(TareaRequest $request): JsonResponse
    {
        $this->tareaService->crear($request->validated(), $request->user()->id);

        return response()->json([
            'message' => 'Tarea registrada correctamente y notificación enviada'
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->tareaService->obtener((int) $id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $tarea = $this->tareaService->avanzarEstado((int) $id, $request->user(), $request->input('nota'));
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'message'       => 'Estado actualizado correctamente',
            'estado_actual' => $tarea->estado_id,
        ]);
    }

    public function actualizarTarea(Request $request, string $id): JsonResponse
    {
        $this->tareaService->actualizar((int) $id, $request->all());

        return response()->json(['message' => 'Tarea actualizada correctamente']);
    }

    public function lineaTiempo(): JsonResponse
    {
        $tareas = $this->tareaService->lineaTiempo()->map(fn($t) => [
            'id'      => $t->id,
            'nombre'  => $t->nombre,
            'estado'  => $t->estado_id,
            'usuario' => $t->usuario->name ?? 'N/A',
            'inicio'  => $t->created_at->format('Y-m-d'),
            'fin'     => $t->fecha_fin,
            'vencida' => now()->gt($t->fecha_fin),
        ]);

        return response()->json($tareas);
    }

    public function agregarNota(Request $request, string $id): JsonResponse
    {
        $request->validate(['nota' => 'required|string|max:1000']);

        try {
            $seguimiento = $this->tareaService->agregarNota((int) $id, $request->user(), $request->nota);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'message' => 'Nota registrada correctamente',
            'data'    => $seguimiento->load('usuario:id,name'),
        ], 201);
    }

    public function historial(string $id): JsonResponse
    {
        $historial = $this->tareaService->historial((int) $id)->map(fn($s) => [
            'id'              => $s->id,
            'tipo'            => $s->tipo,
            'nota'            => $s->nota,
            'estado_anterior' => $s->estado_anterior,
            'estado_nuevo'    => $s->estado_nuevo,
            'usuario'         => $s->usuario->name ?? 'N/A',
            'fecha'           => $s->created_at->format('Y-m-d H:i'),
        ]);

        return response()->json($historial);
    }

    public function resumenMensualFiltrado(Request $request): JsonResponse
    {
        return response()->json($this->tareaService->resumenMensualFiltrado($request));
    }
}
