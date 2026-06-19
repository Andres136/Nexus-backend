<?php

namespace App\Http\Controllers\Nomina;

use App\Exports\NominaPucExport;
use App\Exports\NominaPlanoExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\LiquidarNominaMasivaRequest;
use App\Http\Requests\Nomina\LiquidarNominaRequest;
use App\Http\Requests\Nomina\StoreNominaRequest;
use App\Http\Requests\Nomina\UpdateNominaRequest;
use App\Models\Nomina\Contratacion;
use App\Models\Nomina\Nomina;
use App\Services\Nomina\ConfiguracionNominaService;
use App\Services\Nomina\NominaPucPayloadService;
use App\Services\Nomina\NominaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NominaController extends Controller
{
    public function __construct(
        private readonly NominaService $nominaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'user_id' => $request->query('user_id'),
                'jornada_laboral_id' => $request->query('jornada_laboral_id'),
                'periodo_inicio' => $request->query('periodo_inicio'),
                'periodo_fin' => $request->query('periodo_fin'),
                'search' => $request->query('search'),
                'per_page' => $request->query('per_page', 15),
            ];

            $data = $this->nominaService->getAll($filters);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al listar nóminas', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener las nóminas.'], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $data = $this->nominaService->getByUuid($uuid);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error al obtener nómina', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Nómina no encontrada.'], 404);
        }
    }

    public function store(StoreNominaRequest $request): JsonResponse
    {
        try {
            $data = $this->nominaService->store($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Nómina creada exitosamente.',
                'data' => $data,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear nómina', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al crear la nómina.'], 500);
        }
    }

    public function update(UpdateNominaRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $this->nominaService->update($uuid, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Nómina actualizada exitosamente.',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar nómina', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al actualizar la nómina.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->nominaService->destroy($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Nómina eliminada exitosamente.',
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al eliminar nómina', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al eliminar la nómina.'], 500);
        }
    }

    /**
     * Calcula y liquida la nómina de un empleado para el período dado.
     * Las horas se obtienen automáticamente de las WorkSessions del período.
     *
     * POST /nomina/nominas/liquidar
     * Body: { user_id, periodo_inicio, periodo_fin, jornada_laboral_id, descuento_id? }
     */
    public function resumen(Request $request): JsonResponse
    {
        try {
            $inicio = $request->query('periodo_inicio');
            $fin = $request->query('periodo_fin');

            $empleadosActivos = Contratacion::where('status', 1)->count();

            $query = Nomina::query();
            if ($inicio && $fin) {
                $query->where('periodo_inicio', '>=', $inicio)
                    ->where('periodo_fin', '<=', $fin);
            }

            $nominaBruta = (float) $query->sum('total_devengado');
            $deducciones = (float) $query->sum('total_deducciones');
            $nominaNeta = (float) $query->sum('salario_neto');
            $pagosRealizados = (float) $query->where('liquidada', true)->sum('salario_neto');

            return response()->json([
                'success' => true,
                'data' => [
                    'empleados_activos' => $empleadosActivos,
                    'nomina_bruta' => $nominaBruta,
                    'deducciones' => $deducciones,
                    'nomina_neta' => $nominaNeta,
                    'pagos_realizados' => $pagosRealizados,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener resumen de nómina', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener el resumen.'], 500);
        }
    }

    public function liquidar(LiquidarNominaRequest $request): JsonResponse
    {
        try {
            $nomina = $this->nominaService->liquidar($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Nómina liquidada exitosamente.',
                'data' => $nomina,
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró contrato activo o configuración de tarifas para el empleado.',
            ], 422);
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al liquidar nómina', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al liquidar la nómina.'], 500);
        }
    }

    public function liquidarMasivo(LiquidarNominaMasivaRequest $request): JsonResponse
    {
        try {
            $resultado = $this->nominaService->liquidarMasivo($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Liquidación masiva procesada.',
                'data' => $resultado,
            ], 201);
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al liquidar nómina masiva', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al liquidar la nómina masiva.'], 500);
        }
    }

    public function preliquidar(LiquidarNominaRequest $request): JsonResponse
    {
        try {
            $data = $this->nominaService->preliquidar($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Preliquidación calculada exitosamente.',
                'data' => $data,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró contrato activo o configuración de tarifas para el empleado.',
            ], 422);
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al preliquidar nómina', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al preliquidar la nómina.'], 500);
        }
    }

    public function exportarPlano(Request $request): JsonResponse|BinaryFileResponse
    {
        $validator = Validator::make($request->query(), [
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
            'empresa_id' => 'nullable|integer|exists:empresas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $inicio = $request->query('periodo_inicio');
        $fin = $request->query('periodo_fin');
        $empresaId = $request->query('empresa_id');

        $nominas = Nomina::with([
            'empleado:id,name,email',
            'contratacion:id,tipo_documento,numero_documento,cargo,empresa_id',
        ])
            ->where('liquidada', true)
            ->where('periodo_inicio', '>=', $inicio)
            ->where('periodo_fin', '<=', $fin)
            ->when($empresaId, fn ($q) => $q->whereHas('contratacion', fn ($contrato) => $contrato->where('empresa_id', $empresaId)))
            ->orderBy('user_id')
            ->get();

        if ($nominas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay nóminas liquidadas en el período seleccionado.',
            ], 422);
        }

        $empresaSuffix = $empresaId ? "_empresa_{$empresaId}" : '';
        $filename = "nomina_liquidada_{$inicio}_{$fin}{$empresaSuffix}.xlsx";

        return Excel::download(new NominaPlanoExport($nominas, $inicio, $fin), $filename);
    }

    public function pucPayload(string $uuid, NominaPucPayloadService $payloadService): JsonResponse
    {
        try {
            $payload = $payloadService->generar($uuid);

            if (! $payload['valido']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faltan cuentas PUC para conceptos de nómina.',
                    'data' => $payload,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $payload,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nómina no encontrada.',
            ], 404);
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al generar payload PUC de nómina', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el payload PUC de nómina.',
            ], 500);
        }
    }

    public function aprobarContabilidad(string $uuid, NominaPucPayloadService $payloadService): JsonResponse
    {
        try {
            $nomina = $this->nominaService->aprobarContabilidad($uuid, $payloadService);

            return response()->json([
                'success' => true,
                'message' => 'Nómina aprobada por contabilidad.',
                'data' => $nomina,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nómina no encontrada.',
            ], 404);
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al aprobar contabilidad de nómina', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la nómina en contabilidad.',
            ], 500);
        }
    }

    public function cerrarPeriodo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $count = $this->nominaService->cerrarPeriodo(
                $request->input('periodo_inicio'),
                $request->input('periodo_fin')
            );

            return response()->json([
                'success' => true,
                'message' => "Período cerrado correctamente. Nóminas afectadas: {$count}.",
            ]);
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al cerrar período contable de nómina', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar el período contable.',
            ], 500);
        }
    }

    public function exportarPucExcel(Request $request, NominaPucPayloadService $payloadService): JsonResponse|BinaryFileResponse
    {
        $validator = Validator::make($request->query(), [
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $inicio = $request->query('periodo_inicio');
            $fin = $request->query('periodo_fin');
            [$nominas, $rows] = $this->buildPucRows($inicio, $fin, $payloadService);

            $this->nominaService->marcarPeriodoExportado($nominas);

            return Excel::download(
                new NominaPucExport($rows),
                "puc_nomina_{$inicio}_{$fin}.xlsx"
            );
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al exportar PUC de nómina en Excel', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el PUC en Excel.',
            ], 500);
        }
    }

    public function exportarPucPdf(Request $request, NominaPucPayloadService $payloadService): JsonResponse|Response
    {
        $validator = Validator::make($request->query(), [
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $inicio = $request->query('periodo_inicio');
            $fin = $request->query('periodo_fin');
            [$nominas, $rows] = $this->buildPucRows($inicio, $fin, $payloadService);

            $pdf = Pdf::loadView('pdf.nomina_puc', [
                'rows' => $rows,
                'periodoInicio' => $inicio,
                'periodoFin' => $fin,
            ])->setPaper('letter', 'landscape');

            $this->nominaService->marcarPeriodoExportado($nominas);

            return $pdf->download("puc_nomina_{$inicio}_{$fin}.pdf");
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al exportar PUC de nómina en PDF', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el PUC en PDF.',
            ], 500);
        }
    }

    private function buildPucRows(string $periodoInicio, string $periodoFin, NominaPucPayloadService $payloadService): array
    {
        $nominas = $this->nominaService->getNominasPeriodoContable($periodoInicio, $periodoFin);

        if ($nominas->isEmpty()) {
            throw new \LogicException('No hay nóminas liquidadas en el período seleccionado.');
        }

        $rows = collect();

        foreach ($nominas as $nomina) {
            $payload = $payloadService->generar($nomina->uuid);

            if (! $payload['valido']) {
                throw new \LogicException("La nómina {$nomina->uuid} tiene cuentas PUC pendientes por configurar.");
            }

            $asientos = collect($payload['contabilidad']['asientos'] ?? []);
            $rows = $rows->merge($asientos->map(function ($asiento) use ($nomina) {
                return [
                    'nomina_uuid' => $nomina->uuid,
                    'empleado' => $nomina->empleado?->name ?? '—',
                    'documento' => $nomina->contratacion?->numero_documento ?? '—',
                    'periodo_inicio' => $nomina->periodo_inicio?->format('Y-m-d'),
                    'periodo_fin' => $nomina->periodo_fin?->format('Y-m-d'),
                    'concepto' => $asiento['concepto'] ?? '',
                    'codigo' => $asiento['codigo'] ?? '',
                    'cuenta_puc' => $asiento['cuenta_puc']['numero'] ?? '',
                    'cuenta_nombre' => $asiento['cuenta_puc']['nombre'] ?? '',
                    'naturaleza' => $asiento['naturaleza'] ?? '',
                    'valor' => (float) ($asiento['valor'] ?? 0),
                ];
            }));
        }

        return [$nominas, $rows];
    }

    public function desprendible($uuid)
    {
        $nomina = Nomina::with([
            'empleado',
            'contratacion.empresa',
            'descuento',
        ])->where('uuid', $uuid)->firstOrFail();

        $pdf = Pdf::loadView('pdf.desprendible_pago', [
            'nomina' => $nomina,
            'empresa' => $nomina->contratacion?->empresa,
            'firmaTalentoHumanoPath' => app(ConfiguracionNominaService::class)->firmaTalentoHumanoPath(),
        ])->setPaper('letter', 'portrait');

        return $pdf->download("desprendible_{$nomina->uuid}.pdf");
    }

    public function enviarDesprendible($uuid, Request $request): JsonResponse
    {
        $nomina = Nomina::with([
            'empleado',
            'contratacion.empresa',
            'descuento',
        ])->where('uuid', $uuid)->firstOrFail();

        $correo = $request->input('correo', $nomina->contratacion?->correo);

        $validator = Validator::make(['correo' => $correo], [
            'correo' => 'required|email|max:255',
        ], [
            'correo.required' => 'Debes indicar un correo para enviar el desprendible.',
            'correo.email' => 'El correo debe ser un correo electrónico válido.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('correo'),
            ], 422);
        }

        $pdf = Pdf::loadView('pdf.desprendible_pago', [
            'nomina' => $nomina,
            'empresa' => $nomina->contratacion?->empresa,
            'firmaTalentoHumanoPath' => app(ConfiguracionNominaService::class)->firmaTalentoHumanoPath(),
        ])->setPaper('letter', 'portrait');

        $nombreArchivo = "desprendible_{$nomina->uuid}.pdf";

        Mail::raw(
            'Adjuntamos el desprendible de pago solicitado.',
            function ($message) use ($correo, $pdf, $nombreArchivo) {
                $message->to($correo)
                    ->subject('Desprendible de pago')
                    ->attachData($pdf->output(), $nombreArchivo, ['mime' => 'application/pdf']);
            }
        );

        return response()->json([
            'success' => true,
            'message' => "Desprendible enviado a {$correo}.",
        ]);
    }
}
