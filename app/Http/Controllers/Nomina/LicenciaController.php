<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\GestionLicenciaRequest;
use App\Http\Requests\Nomina\StoreLicenciaRequest;
use App\Services\Nomina\LicenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LicenciaController extends Controller
{
    public function __construct(private readonly LicenciaService $service) {}

    public function index(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data'    => $this->service->getAll($request->only([
                    'search', 'user_id', 'sede_id', 'status',
                    'fecha_desde', 'fecha_hasta', 'per_page',
                ])),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al listar licencias', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener las licencias.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->service->getByUuid($uuid)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Licencia no encontrada.'], 404);
        }
    }

    public function store(StoreLicenciaRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['soporte']  = $request->file('soporte');
            $data['user_id']  = Auth::id();

            $licencia = $this->service->store($data);

            return response()->json([
                'success' => true,
                'message' => 'Licencia registrada exitosamente.',
                'data'    => $licencia,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al registrar licencia', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al registrar la licencia.'], 500);
        }
    }

    public function aprobar(GestionLicenciaRequest $request, string $uuid): JsonResponse
    {
        try {
            $licencia = $this->service->aprobar($uuid, $request->input('observacion'));
            return response()->json(['success' => true, 'message' => 'Licencia aprobada.', 'data' => $licencia]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al aprobar licencia', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al aprobar la licencia.'], 500);
        }
    }

    public function rechazar(GestionLicenciaRequest $request, string $uuid): JsonResponse
    {
        try {
            $licencia = $this->service->rechazar($uuid, $request->input('observacion'));
            return response()->json(['success' => true, 'message' => 'Licencia rechazada.', 'data' => $licencia]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al rechazar licencia', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al rechazar la licencia.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->destroy($uuid);
            return response()->json(['success' => true, 'message' => 'Licencia eliminada.']);
        } catch (\Exception $e) {
            Log::error('Error al eliminar licencia', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar la licencia.'], 500);
        }
    }
}
