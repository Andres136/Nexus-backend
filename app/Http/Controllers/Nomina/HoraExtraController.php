<?php

namespace App\Http\Controllers\Nomina;

use App\Exports\GenericExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\GestionHoraExtraRequest;
use App\Http\Requests\Nomina\StoreHoraExtraRequest;
use App\Http\Requests\Nomina\UpdateHoraExtraRequest;
use App\Models\Nomina\HoraExtra;
use App\Services\Nomina\HoraExtraService;
use App\Services\Nomina\KioskoDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HoraExtraController extends Controller
{
    public function __construct(
        private readonly HoraExtraService $horaExtraService,
        private readonly KioskoDeviceService $kioskoDeviceService
    ) {}

    private function filtrosDesde(Request $request): array
    {
        return [
            'user_id' => $request->query('user_id'),
            'sede_id' => $request->query('sede_id'),
            'kiosko_device_id' => $request->query('kiosko_device_id'),
            'status' => $request->query('status'),
            'tipo' => $request->query('tipo'),
            'search' => $request->query('search'),
            'mine' => $request->boolean('mine'),
            'fecha_desde' => $request->query('fecha_desde'),
            'fecha_hasta' => $request->query('fecha_hasta'),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $this->filtrosDesde($request);
            $filters['per_page'] = $request->query('per_page', 15);

            $data = $this->horaExtraService->getAll($filters);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al listar horas extras', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener las horas extras.'], 500);
        }
    }

    /**
     * PATCH /nomina/horas-extras/aprobar-todas
     * Aprueba todas las horas extras pendientes que cumplan los filtros aplicados.
     */
    public function aprobarTodas(GestionHoraExtraRequest $request): JsonResponse
    {
        try {
            $cantidad = $this->horaExtraService->aprobarTodas(
                $this->filtrosDesde($request),
                $request->input('observacion')
            );

            return response()->json([
                'success' => true,
                'message' => $cantidad > 0
                    ? "Se aprobaron {$cantidad} hora(s) extra(s)."
                    : 'No hay horas extras pendientes para aprobar con los filtros seleccionados.',
                'data' => ['aprobadas' => $cantidad],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al aprobar horas extras en lote', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al aprobar las horas extras.'], 500);
        }
    }

    /**
     * GET /nomina/horas-extras/exportar
     * Exporta a Excel las horas extras ya aprobadas que cumplan los filtros aplicados.
     */
    public function exportar(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $registros = $this->horaExtraService->getAprobadasParaExportar($this->filtrosDesde($request));

            if ($registros->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay horas extras aprobadas para exportar con los filtros seleccionados.',
                ], 422);
            }

            $filas = $registros->map(fn (HoraExtra $h) => [
                'Empleado' => $h->empleado?->name,
                'Email' => $h->empleado?->email,
                'Sede' => $h->sede?->nombre,
                'Fecha' => optional($h->fecha)->format('Y-m-d'),
                'Hora inicio' => $h->hora_inicio,
                'Hora fin' => $h->hora_fin,
                'Horas' => $h->horas,
                'Tipo' => $h->tipo,
                'Motivo' => $h->motivo,
                'Solicitado por' => $h->solicitante?->name,
                'Autorizado por' => $h->supervisor?->name,
                'Fecha gestión' => optional($h->fecha_gestion)->format('Y-m-d H:i'),
                'Observación' => $h->observacion_gestion,
            ]);

            $headings = ['Empleado', 'Email', 'Sede', 'Fecha', 'Hora inicio', 'Hora fin', 'Horas', 'Tipo', 'Motivo', 'Solicitado por', 'Autorizado por', 'Fecha gestión', 'Observación'];
            $filename = 'horas_extras_aprobadas_' . now()->format('Y-m-d_His') . '.xlsx';

            return Excel::download(new GenericExport($filas, $headings), $filename);
        } catch (\Exception $e) {
            Log::error('Error al exportar horas extras', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al exportar las horas extras.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $data = $this->horaExtraService->getByUuid($uuid);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al obtener hora extra', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Hora extra no encontrada.'], 404);
        }
    }

    public function kioskIndex(Request $request): JsonResponse
    {
        try {
            $device = $this->kioskoDeviceService->resolveKioskoDevice($request, $request->ip());
            $userId = $request->query('user_id');

            if (! $userId) {
                return response()->json(['success' => false, 'message' => 'user_id requerido.'], 422);
            }

            $horas = HoraExtra::where('user_id', $userId)
                ->whereDate('fecha', now()->toDateString())
                ->where('status', 'aprobada')
                ->where(function ($query) use ($device) {
                    $query->whereNull('kiosko_device_id')
                        ->orWhere('kiosko_device_id', $device->id);
                })
                ->get(['uuid', 'fecha', 'hora_inicio', 'hora_fin', 'horas', 'tipo', 'status', 'observacion_gestion']);

            return response()->json([
                'success' => true,
                'data' => [
                    'horas' => $horas,
                    'total_horas' => round((float) $horas->sum('horas'), 2),
                ],
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Error al listar horas extras desde kiosko', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener horas extras.'], 500);
        }
    }

    /**
     * Crea el registro de horas extras para uno o varios empleados.
     *
     * POST /nomina/horas-extras
     * Body: { users: [1,2,3], fecha, horas, tipo, motivo? }
     */
    public function store(StoreHoraExtraRequest $request): JsonResponse
    {
        try {
            $registros = $this->horaExtraService->store($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Se registraron horas extras para {$registros->count()} empleado(s).",
                'data' => $registros,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al registrar horas extras', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al registrar las horas extras.'], 500);
        }
    }

    /**
     * PATCH /nomina/horas-extras/{uuid}
     * Edita una solicitud propia mientras esté en estado pendiente
     * (incluye las que fueron desaprobadas y volvieron a pendiente).
     */
    public function update(UpdateHoraExtraRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->horaExtraService->actualizar($uuid, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de hora extra actualizada.',
                'data' => $data,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al editar hora extra', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al editar la solicitud.'], 500);
        }
    }

    /**
     * PATCH /nomina/horas-extras/{uuid}/aprobar
     */
    public function aprobar(GestionHoraExtraRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->horaExtraService->aprobar($uuid, $request->input('observacion'));

            return response()->json([
                'success' => true,
                'message' => 'Horas extras aprobadas.',
                'data' => $data,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al aprobar hora extra', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al aprobar la hora extra.'], 500);
        }
    }

    /**
     * PATCH /nomina/horas-extras/{uuid}/rechazar
     */
    public function rechazar(GestionHoraExtraRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->horaExtraService->rechazar($uuid, $request->input('observacion'));

            return response()->json([
                'success' => true,
                'message' => 'Horas extras rechazadas.',
                'data' => $data,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al rechazar hora extra', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al rechazar la hora extra.'], 500);
        }
    }

    /**
     * PATCH /nomina/horas-extras/{uuid}/desaprobar
     * Revierte una hora extra aprobada a estado pendiente. Solo responsables de departamento.
     */
    public function desaprobar(GestionHoraExtraRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->horaExtraService->desaprobar($uuid, $request->input('observacion'));

            return response()->json([
                'success' => true,
                'message' => 'Hora extra desaprobada.',
                'data' => $data,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al desaprobar hora extra', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al desaprobar la hora extra.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->horaExtraService->destroy($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Hora extra eliminada.',
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al eliminar hora extra', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al eliminar la hora extra.'], 500);
        }
    }
}
