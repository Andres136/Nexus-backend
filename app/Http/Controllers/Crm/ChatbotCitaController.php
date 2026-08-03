<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ChatbotCitaRequest;
use App\Models\Crm\ChatbotCita;
use App\Services\Crm\ChatbotCitaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotCitaController extends Controller
{
    public function __construct(private readonly ChatbotCitaService $citaService)
    {
    }

    public function index(Request $request)
    {
        $citas = $this->citaService->listar(Auth::user(), [
            'desde' => $request->input('desde'),
            'hasta' => $request->input('hasta'),
        ]);

        return response()->json($citas);
    }

    public function store(ChatbotCitaRequest $request)
    {
        $cita = $this->citaService->crear($request->validated());

        return response()->json([
            'message' => 'Cita creada correctamente',
            'cita' => $cita,
        ], 201);
    }

    public function update(ChatbotCitaRequest $request, string $id)
    {
        $cita = ChatbotCita::findOrFail($id);
        abort_unless($this->citaService->puedeGestionar($cita, Auth::user()), 403, 'No autorizado');

        return response()->json([
            'message' => 'Cita actualizada correctamente',
            'cita' => $this->citaService->actualizar($cita, $request->validated()),
        ]);
    }

    public function destroy(string $id)
    {
        $cita = ChatbotCita::findOrFail($id);
        abort_unless($this->citaService->puedeGestionar($cita, Auth::user()), 403, 'No autorizado');

        $this->citaService->eliminar($cita);

        return response()->json(['message' => 'Cita eliminada correctamente']);
    }
}
