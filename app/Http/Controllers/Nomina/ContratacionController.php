<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreContratacionRequest;
use App\Http\Requests\Nomina\UpdateContratacionRequest;
use App\Models\Nomina\Contratacion;
use App\Services\Nomina\ContratacionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContratacionController extends Controller
{
    public function __construct(
        private ContratacionService $contratacionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search'       => $request->query('search'),
                'fecha_inicio' => $request->query('fecha_inicio'),
                'fecha_fin'    => $request->query('fecha_fin'),
                'user_id'      => $request->query('user_id'),
                'status'       => $request->query('status'),
                'per_page'     => $request->query('per_page', 10),
            ];

            $data = $this->contratacionService->getAll($filters);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al listar contrataciones', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener las contrataciones.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $data = $this->contratacionService->getByUuid($uuid);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al obtener contratación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Contratación no encontrada.'], 404);
        }
    }

    public function store(StoreContratacionRequest $request): JsonResponse
    {
        try {
            $data = $this->contratacionService->create($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Contratación creada correctamente.',
                'data'    => $data,
            ], 201);
        } catch (\Exception $e) {
            if ($e instanceof \LogicException) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            Log::error('Error al crear contratación', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al crear la contratación.'], 500);
        }
    }

    public function update(UpdateContratacionRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->contratacionService->update($uuid, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Contratación actualizada correctamente.',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar contratación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar la contratación.'], 500);
        }
    }

    public function cambiarEstado(Request $request, string $uuid): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|boolean',
            ]);

            $data = $this->contratacionService->cambiarEstado($uuid, (bool) $validated['status']);

            return response()->json([
                'success' => true,
                'message' => $data->status ? 'Contratación activada correctamente.' : 'Contratación inactivada correctamente.',
                'data'    => $data,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estado inválido.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de contratación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al cambiar el estado de la contratación.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->contratacionService->delete($uuid);
            return response()->json([
                'success' => true,
                'message' => 'Contratación eliminada correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar contratación', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar la contratación.'], 500);
        }
    }

    public function getEmpleados(Request $request): JsonResponse
    {
        $empleados = $this->contratacionService->getEmpleadosOptions([
            'con_contrato' => $request->boolean('con_contrato'),
        ]);

        return response()->json($empleados);
    }

    public function certificado($uuid, Request $request)
    {
        $contratacion = Contratacion::with(['usuario', 'empresa', 'tipoContrato'])
            ->where('uuid', $uuid)->firstOrFail();

        $pdf = Pdf::loadView('pdf.certificado_laboral', [
            'contratacion' => $contratacion,
            'empresa'      => $contratacion->empresa,
            'dirigido_a'   => $request->input('dirigido_a'),
            'fecha_actual' => now()->locale('es')->translatedFormat('d \d\e F \d\e Y'),
        ])->setPaper('letter', 'portrait');

        return $pdf->download("certificado_{$contratacion->uuid}.pdf");
    }

    public function enviarCertificado($uuid, Request $request): JsonResponse
    {
        $contratacion = Contratacion::with(['usuario', 'empresa', 'tipoContrato'])
            ->where('uuid', $uuid)->firstOrFail();

        $correo = $request->input('correo', $contratacion->correo);

        $validator = Validator::make(['correo' => $correo], [
            'correo' => 'required|email|max:255',
        ], [
            'correo.required' => 'Debes indicar un correo para enviar el certificado.',
            'correo.email'    => 'El correo debe ser un correo electrónico válido.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('correo'),
            ], 422);
        }

        $pdf = Pdf::loadView('pdf.certificado_laboral', [
            'contratacion' => $contratacion,
            'empresa'      => $contratacion->empresa,
            'dirigido_a'   => $request->input('dirigido_a'),
            'fecha_actual' => now()->locale('es')->translatedFormat('d \d\e F \d\e Y'),
        ])->setPaper('letter', 'portrait');

        $nombreArchivo = "certificado_{$contratacion->uuid}.pdf";

        Mail::raw(
            "Adjuntamos el certificado laboral solicitado.",
            function ($message) use ($correo, $pdf, $nombreArchivo) {
                $message->to($correo)
                    ->subject('Certificado laboral')
                    ->attachData($pdf->output(), $nombreArchivo, ['mime' => 'application/pdf']);
            }
        );

        return response()->json([
            'success' => true,
            'message' => "Certificado enviado a {$correo}.",
        ]);
    }
}
