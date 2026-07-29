<?php

namespace App\Http\Controllers\Nomina;

use App\EstadoEnum;
use App\Http\Controllers\Controller;
use App\Models\Departamentos;
use App\Models\User;
use App\Services\Nomina\PreliquidacionNominaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LoteAprobacionNominaController extends Controller
{
    public function __construct(
        private readonly PreliquidacionNominaService $preliquidacionService,
    ) {}

    public function responsables(): JsonResponse
    {
        $responsableIds = Departamentos::whereNotNull('responsable_id')->distinct()->pluck('responsable_id');

        $responsables = User::whereIn('id', $responsableIds)
            ->where('estado_id', EstadoEnum::ACTIVO->value)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json(['success' => true, 'data' => $responsables]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
            'jornada_laboral_id' => 'required|integer|exists:jornada_laborals,id',
            'sede_id' => 'nullable|integer|exists:sedes,id',
            'empresa_id' => 'nullable|integer|exists:empresas,id',
            'descontar_tardanzas' => 'nullable|boolean',
            'excluir_tardanza_ids' => 'nullable|array',
            'excluir_tardanza_ids.*' => 'integer',
            'excluir_permiso_ids' => 'nullable|array',
            'excluir_permiso_ids.*' => 'integer',
            'responsable_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $validated = $validator->validated();
            $resultado = $this->preliquidacionService->crearLoteAprobacion($validated, (int) $validated['responsable_id']);

            return response()->json([
                'success' => true,
                'message' => "Se enviaron {$resultado['total_generados']} preliquidaciones a {$resultado['responsable']['name']} para su aprobación.",
                'data' => $resultado,
            ], 201);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear lote de aprobación de nómina', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al enviar el lote a aprobación.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->preliquidacionService->getLoteAprobacion($uuid)]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Lote no encontrado.'], 404);
        }
    }

    public function aprobar(string $uuid): JsonResponse
    {
        try {
            $resultado = $this->preliquidacionService->aprobarLote($uuid, (int) Auth::id());

            return response()->json([
                'success' => true,
                'message' => "Se liquidaron {$resultado['total_liquidados']} nóminas".
                    ($resultado['total_errores'] > 0 ? " ({$resultado['total_errores']} con error)." : '.'),
                'data' => $resultado,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Lote no encontrado.'], 404);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al aprobar lote de nómina', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al aprobar el lote.'], 500);
        }
    }
}
