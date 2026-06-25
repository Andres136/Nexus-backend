<?php

namespace App\Http\Controllers;

use App\Http\Requests\CapacitacionRequest;
use App\Services\CapacitacionService;
use Illuminate\Http\Request;

class CapacitacionController extends Controller
{
    public function __construct(protected CapacitacionService $capacitacionService) {}

    public function index(Request $request)
    {
        return response()->json(
            $this->capacitacionService->getAll(
                $request->only(['search', 'fecha_desde', 'fecha_hasta', 'estado', 'user_id', 'propias']),
                $request->user()
            )
        );
    }

    public function store(CapacitacionRequest $request)
    {
        $capacitacion = $this->capacitacionService->store($request->validated(), $request->user());

        return response()->json([
            'message' => 'Capacitación creada correctamente.',
            'capacitacion' => $capacitacion,
        ], 201);
    }

    public function show(Request $request, string $uuid)
    {
        return response()->json(
            $this->capacitacionService->show($uuid, $request->user())
        );
    }

    public function update(CapacitacionRequest $request, string $uuid)
    {
        $capacitacion = $this->capacitacionService->update($uuid, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Capacitación actualizada correctamente.',
            'capacitacion' => $capacitacion,
        ]);
    }

    public function destroy(Request $request, string $uuid)
    {
        $this->capacitacionService->destroy($uuid, $request->user());

        return response()->json(['message' => 'Capacitación eliminada correctamente.']);
    }
}
