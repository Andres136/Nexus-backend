<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\GuardarHorarioUsuarioBloqueRequest;
use App\Services\Nomina\HorarioUsuarioBloqueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HorarioUsuarioBloqueController extends Controller
{
    public function __construct(private readonly HorarioUsuarioBloqueService $service) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        try {
            $data = isset($validated['user_id'])
                ? $this->service->porUsuario((int) $validated['user_id'])
                : $this->service->todos();

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al listar horario por bloques del usuario', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener el horario por bloques.'], 500);
        }
    }

    public function store(GuardarHorarioUsuarioBloqueRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            return response()->json([
                'success' => true,
                'message' => 'Horario por bloques guardado correctamente.',
                'data' => $this->service->guardarSemana((int) $data['user_id'], $data['bloques']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al guardar horario por bloques del usuario', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al guardar el horario por bloques.'], 500);
        }
    }
}
