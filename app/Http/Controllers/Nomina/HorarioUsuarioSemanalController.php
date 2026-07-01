<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\GuardarHorarioUsuarioSemanalRequest;
use App\Services\Nomina\HorarioUsuarioSemanalService;
use App\Services\Nomina\KioskoDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HorarioUsuarioSemanalController extends Controller
{
    public function __construct(
        private readonly HorarioUsuarioSemanalService $service,
        private readonly KioskoDeviceService $kioskoDeviceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->porUsuario((int) $validated['user_id']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al listar horarios semanales por usuario', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener el horario semanal.'], 500);
        }
    }

    public function store(GuardarHorarioUsuarioSemanalRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Horario semanal del usuario guardado correctamente.',
                'data' => $this->service->guardarSemana($request->validated()),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al guardar horario semanal por usuario', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al guardar el horario semanal.'], 500);
        }
    }

    public function kioskHoy(Request $request): JsonResponse
    {
        try {
            $this->kioskoDeviceService->resolveKioskoDevice($request, $request->ip());

            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->service->porUsuarioYFecha((int) $validated['user_id'], now(config('app.timezone'))->toDateString()),
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Error al obtener horario semanal desde kiosko', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener el horario del usuario.'], 500);
        }
    }
}
