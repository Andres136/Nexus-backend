<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\Contratacion;
use App\Models\Nomina\Incapacidad;
use App\Models\Nomina\Licencia;
use App\Models\Nomina\Nomina;
use App\Models\Nomina\Permiso;
use App\Models\Nomina\Vacacion;
use App\Http\Requests\Nomina\StorePortalVacacionRequest;
use App\Http\Requests\Nomina\StoreIncapacidadRequest;
use App\Http\Requests\Nomina\StoreLicenciaRequest;
use App\Http\Requests\Nomina\StorePermisoRequest;
use App\Models\Nomina\SeguridadSocial;
use App\Services\Nomina\IncapacidadService;
use App\Services\Nomina\LicenciaService;
use App\Services\Nomina\PermisoService;
use App\Services\Nomina\VacacionService;
use App\Services\Nomina\ConfiguracionNominaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PortalEmpleadoController extends Controller
{
    // ─── Desprendibles ───────────────────────────────────────────────────────

    public function nominas(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 15);

            $data = Nomina::with(['empleado:id,name,email', 'contratacion:id,uuid,cargo'])
                ->where('user_id', Auth::id())
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Portal: error al listar nóminas', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener los desprendibles.'], 500);
        }
    }

    // ─── Certificado laboral ──────────────────────────────────────────────────

    public function certificado(Request $request): mixed
    {
        try {
            $contratacion = Contratacion::with(['usuario', 'empresa', 'tipoContrato'])
                ->where('users_id', Auth::id())
                ->latest('inicio_contratacion')
                ->firstOrFail();

            $pdf = Pdf::loadView('pdf.certificado_laboral', [
                'contratacion' => $contratacion,
                'empresa'      => $contratacion->empresa,
                'dirigido_a'   => $request->input('dirigido_a'),
                'fecha_actual' => now()->locale('es')->translatedFormat('d \d\e F \d\e Y'),
                'firmaTalentoHumanoPath' => app(ConfiguracionNominaService::class)->firmaTalentoHumanoPath(),
            ])->setPaper('letter', 'portrait');

            return $pdf->download("certificado_{$contratacion->uuid}.pdf");
        } catch (\Exception $e) {
            Log::error('Portal: error al generar certificado', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'No se encontró un contrato activo.'], 404);
        }
    }

    public function enviarCertificado(Request $request): JsonResponse
    {
        try {
            $contratacion = Contratacion::with(['usuario', 'empresa', 'tipoContrato'])
                ->where('users_id', Auth::id())
                ->latest('inicio_contratacion')
                ->firstOrFail();

            $correo = $request->input('correo', $contratacion->correo);

            $validator = Validator::make(['correo' => $correo], [
                'correo' => 'required|email|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            $pdf = Pdf::loadView('pdf.certificado_laboral', [
                'contratacion' => $contratacion,
                'empresa'      => $contratacion->empresa,
                'dirigido_a'   => $request->input('dirigido_a'),
                'fecha_actual' => now()->locale('es')->translatedFormat('d \d\e F \d\e Y'),
                'firmaTalentoHumanoPath' => app(ConfiguracionNominaService::class)->firmaTalentoHumanoPath(),
            ])->setPaper('letter', 'portrait');

            $nombreArchivo = "certificado_{$contratacion->uuid}.pdf";

            Mail::raw('Adjuntamos el certificado laboral solicitado.', function ($message) use ($correo, $pdf, $nombreArchivo) {
                $message->to($correo)
                    ->subject('Certificado laboral')
                    ->attachData($pdf->output(), $nombreArchivo, ['mime' => 'application/pdf']);
            });

            return response()->json(['success' => true, 'message' => "Certificado enviado a {$correo}."]);
        } catch (\Exception $e) {
            Log::error('Portal: error al enviar certificado', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al enviar el certificado.'], 500);
        }
    }

    // ─── Vacaciones ───────────────────────────────────────────────────────────

    public function vacaciones(Request $request): JsonResponse
    {
        try {
            $data = Vacacion::with([
                'empleado:id,name,email,sede_id',
                'empleado.sede:id,nombre',
                'supervisor:id,name,email',
            ])
                ->where('user_id', Auth::id())
                ->orderByDesc('created_at')
                ->paginate($request->query('per_page', 15));

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener las vacaciones.'], 500);
        }
    }

    public function resumenVacaciones(VacacionService $vacacionService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $vacacionService->resumen((int) Auth::id()),
        ]);
    }

    public function solicitarVacaciones(
        StorePortalVacacionRequest $request,
        VacacionService $vacacionService
    ): JsonResponse {
        try {
            $vacacion = $vacacionService->store(array_merge(
                $request->validated(),
                ['user_id' => (int) Auth::id()]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de vacaciones registrada.',
                'data' => $vacacion,
            ], 201);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Portal: error al solicitar vacaciones', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la solicitud de vacaciones.',
            ], 500);
        }
    }

    // ─── Permisos ─────────────────────────────────────────────────────────────

    public function permisos(Request $request): JsonResponse
    {
        try {
            $data = Permiso::with(['empleado:id,name,email', 'supervisor:id,name,email'])
                ->where('user_id', Auth::id())
                ->orderByDesc('fecha')
                ->paginate($request->query('per_page', 15));

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener los permisos.'], 500);
        }
    }

    public function solicitarPermiso(StorePermisoRequest $request, PermisoService $service): JsonResponse
    {
        $permiso = $service->store($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Permiso registrado exitosamente.',
            'data' => $permiso,
        ], 201);
    }

    // ─── Licencias ────────────────────────────────────────────────────────────

    public function licencias(Request $request): JsonResponse
    {
        try {
            $data = Licencia::with(['empleado:id,name,email', 'autorizador:id,name'])
                ->where('user_id', Auth::id())
                ->orderByDesc('inicio')
                ->paginate($request->query('per_page', 15));

            $data->getCollection()->transform(function ($item) {
                $item->soporte_url = $item->soporte
                    ? asset('storage/' . $item->soporte)
                    : null;
                return $item;
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener las licencias.'], 500);
        }
    }

    public function solicitarLicencia(StoreLicenciaRequest $request, LicenciaService $service): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = (int) Auth::id();
        $data['soporte'] = $request->file('soporte');
        $licencia = $service->store($data);

        return response()->json([
            'success' => true,
            'message' => 'Licencia registrada exitosamente.',
            'data' => $licencia,
        ], 201);
    }

    // ─── Incapacidades ────────────────────────────────────────────────────────

    public function incapacidades(Request $request): JsonResponse
    {
        try {
            $data = Incapacidad::with([
                    'empleado:id,name,email',
                    'revisor:id,name',
                    'entidadMedica:id,nombre',
                ])
                ->where('user_id', Auth::id())
                ->orderByDesc('inicio')
                ->paginate($request->query('per_page', 15));

            $data->getCollection()->transform(function ($item) {
                $item->soporte_url = $item->soporte
                    ? asset('storage/' . $item->soporte)
                    : null;
                $item->soporte_embed_url = $item->soporte
                    ? url("api/nomina/incapacidades/{$item->uuid}/soporte")
                    : null;
                $item->estado_actual = now()->gt($item->fin) ? 'finalizada' : 'activa';
                $item->estado_revision = $item->estado_revision ?? 'pendiente';
                return $item;
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener las incapacidades.'], 500);
        }
    }

    public function registrarIncapacidad(
        StoreIncapacidadRequest $request,
        IncapacidadService $service
    ): JsonResponse {
        $incapacidad = $service->store($request->validated(), $request->file('soporte'));

        return response()->json([
            'success' => true,
            'message' => 'Incapacidad registrada exitosamente.',
            'data' => $incapacidad,
        ], 201);
    }

    public function soporteIncapacidad(string $uuid)
    {
        $incapacidad = Incapacidad::where('uuid', $uuid)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        abort_unless(
            $incapacidad->soporte && Storage::disk('public')->exists($incapacidad->soporte),
            404,
            'Soporte no encontrado.'
        );

        return response()->file(Storage::disk('public')->path($incapacidad->soporte), [
            'Content-Type' => Storage::disk('public')->mimeType($incapacidad->soporte) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.basename($incapacidad->soporte).'"',
        ]);
    }

    public function entidadesMedicas(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SeguridadSocial::query()
                ->where('status', true)
                ->orderBy('nombre')
                ->get(['id', 'uuid', 'nombre', 'tipo']),
        ]);
    }
}
