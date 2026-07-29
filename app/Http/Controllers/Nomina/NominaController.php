<?php

namespace App\Http\Controllers\Nomina;

use App\Exports\NominaPucExport;
use App\Exports\NominaPlanoExport;
use App\Exports\PreliquidacionLoteExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\LiquidarNominaRequest;
use App\Http\Requests\Nomina\PreliquidarLoteNominaRequest;
use App\Http\Requests\Nomina\RevertirNominaRequest;
use App\Models\Crm\empresa as Empresa;
use App\Models\Nomina\Contratacion;
use App\Models\Nomina\Nomina;
use App\Services\Nomina\ConfiguracionNominaService;
use App\Services\Nomina\NominaPucPayloadService;
use App\Services\Nomina\NominaService;
use App\Services\Nomina\PreliquidacionNominaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NominaController extends Controller
{
    public function __construct(
        private readonly NominaService $nominaService,
        private readonly PreliquidacionNominaService $preliquidacionService,
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

    public function revertir(
        RevertirNominaRequest $request,
        string $uuid,
        NominaPucPayloadService $payloadService
    ): JsonResponse
    {
        try {
            $nomina = $this->nominaService->revertir(
                $uuid,
                $request->validated('motivo'),
                $payloadService
            );

            return response()->json([
                'success' => true,
                'message' => $nomina->estado_contable === 'reversada'
                    ? 'Nómina reversada y asiento contable inverso generado.'
                    : 'Nómina anulada correctamente.',
                'data' => $nomina,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error al revertir nómina', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al revertir la nómina.'], 500);
        }
    }

    public function resumen(Request $request): JsonResponse
    {
        try {
            $inicio = $request->query('periodo_inicio');
            $fin = $request->query('periodo_fin');

            $empleadosActivos = Contratacion::where('status', 1)->count();

            $query = Nomina::where('liquidada', true)->operativas();
            if ($inicio && $fin) {
                $query->where('periodo_inicio', '>=', $inicio)
                    ->where('periodo_fin', '<=', $fin);
            }

            $nominaBruta = (float) $query->sum('total_devengado');
            $deducciones = (float) $query->sum('total_deducciones');
            $nominaNeta = (float) $query->sum('salario_neto');
            $pagosRealizados = (float) (clone $query)->sum('salario_neto');

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

    public function preliquidar(LiquidarNominaRequest $request): JsonResponse
    {
        try {
            $preliquidacion = $this->preliquidacionService->guardar($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Preliquidación guardada como borrador.',
                'data' => $this->preliquidacionPayload($preliquidacion),
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

    public function permisosLiquidacion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->nominaService->obtenerPermisosLiquidacion(
                (int) $validated['user_id'],
                $validated['periodo_inicio'],
                $validated['periodo_fin'],
            ),
        ]);
    }

    public function showPreliquidacion(string $uuid): JsonResponse
    {
        $preliquidacion = $this->preliquidacionService->getByUuid($uuid);

        return response()->json([
            'success' => true,
            'data' => $this->preliquidacionPayload($preliquidacion),
        ]);
    }

    public function agregarAjustePreliquidacion(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'tipo' => 'required|in:devengo,deduccion',
            'concepto' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0.01',
            'afecta_base_aportes' => 'nullable|boolean',
            'motivo' => 'required|string|max:2000',
        ]);

        try {
            $preliquidacion = $this->preliquidacionService->agregarAjuste($uuid, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Ajuste agregado con trazabilidad.',
                'data' => $this->preliquidacionPayload($preliquidacion),
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function eliminarAjustePreliquidacion(string $uuid, string $ajusteUuid): JsonResponse
    {
        try {
            $preliquidacion = $this->preliquidacionService->eliminarAjuste($uuid, $ajusteUuid);

            return response()->json([
                'success' => true,
                'message' => 'Ajuste eliminado.',
                'data' => $this->preliquidacionPayload($preliquidacion),
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function enviarRevisionPreliquidacion(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate(['observacion' => 'nullable|string|max:2000']);

        try {
            $preliquidacion = $this->preliquidacionService->enviarRevision($uuid, $validated['observacion'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Preliquidación marcada en revisión.',
                'data' => $this->preliquidacionPayload($preliquidacion),
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function aprobarPreliquidacion(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate(['observacion' => 'nullable|string|max:2000']);

        try {
            $preliquidacion = $this->preliquidacionService->aprobar($uuid, $validated['observacion'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Preliquidación aprobada.',
                'data' => $this->preliquidacionPayload($preliquidacion),
            ]);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function liquidarPreliquidacion(string $uuid): JsonResponse
    {
        try {
            $nomina = $this->preliquidacionService->liquidar($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Nómina liquidada desde la preliquidación aprobada.',
                'data' => $nomina,
            ], 201);
        } catch (\LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function preliquidarLote(PreliquidarLoteNominaRequest $request): JsonResponse
    {
        try {
            $resultado = $this->nominaService->preliquidarLote($request->validated());

            return response()->json(['success' => true, 'data' => $resultado]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La jornada laboral seleccionada no existe.',
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al preliquidar nómina en lote', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al preliquidar la nómina en lote.'], 500);
        }
    }

    public function excepcionDescuento(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'user_id' => 'required|integer|exists:users,id',
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $validated = $validator->validated();

        return response()->json([
            'success' => true,
            'data' => $this->nominaService->obtenerExcepcionDescuento(
                (int) $validated['user_id'],
                $validated['periodo_inicio'],
                $validated['periodo_fin']
            ),
        ]);
    }

    public function exportarPreliquidacionLote(Request $request): JsonResponse|BinaryFileResponse
    {
        $validator = Validator::make($request->query(), [
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $resultado = $this->nominaService->preliquidarLote($validator->validated());

            $headings = [
                'Empleado', 'Correo', 'Periodo inicio', 'Periodo fin',
                'Horas normales', 'Horas extra diurnas', 'Horas extra nocturnas',
                'Horas festivas', 'Horas nocturnas festivas',
                'Valor horas extra diurnas', 'Valor horas extra nocturnas',
                'Valor horas festivas', 'Valor horas nocturnas festivas',
                'Minutos tardanza', 'Valor tardanzas', '¿Tardanzas descontadas?',
                'Minutos permisos no remunerados', 'Valor permisos no remunerados', '¿Permisos descontados?',
                'Préstamos', 'Salario base devengado', 'Total devengado', 'Total deducciones', 'Neto a pagar',
            ];

            $filas = collect($resultado['empleados'])->map(fn (array $calculo) => [
                $calculo['empleado']['name'],
                $calculo['empleado']['email'],
                $calculo['periodo_inicio'],
                $calculo['periodo_fin'],
                $calculo['horas_normales'],
                $calculo['horas_extras_diurnas'],
                $calculo['horas_extras_nocturnas'],
                $calculo['horas_festivas'],
                $calculo['horas_nocturnas_festivas'],
                $calculo['valor_horas_extras_diurnas'],
                $calculo['valor_horas_extras_nocturnas'],
                $calculo['valor_horas_festivas'],
                $calculo['valor_horas_nocturnas_festivas'],
                $calculo['minutos_tardanza'],
                $calculo['valor_tardanzas'],
                $calculo['descuenta_tardanzas'] ? 'Sí' : 'No',
                $calculo['minutos_permisos_no_remunerados'],
                $calculo['valor_permisos_no_remunerados'],
                $calculo['descuenta_permisos'] ? 'Sí' : 'No',
                $calculo['valor_prestamos'],
                $calculo['salario_base_devengado'],
                $calculo['total_devengado'],
                $calculo['total_deducciones'],
                $calculo['salario_neto'],
            ]);

            $empresaId = $validator->validated()['empresa_id'] ?? null;
            $empresa = $empresaId ? Empresa::find($empresaId) : null;
            $logoPath = null;
            if ($empresa && $empresa->logo) {
                $posiblePath = public_path('storage/'.$empresa->logo);
                if (file_exists($posiblePath) && ! is_dir($posiblePath)) {
                    $logoPath = $posiblePath;
                }
            }

            return Excel::download(
                new PreliquidacionLoteExport(
                    $filas,
                    $headings,
                    $resultado['totales'],
                    $resultado['periodo_inicio'],
                    $resultado['periodo_fin'],
                    $empresa?->nombre,
                    $logoPath
                ),
                "preliquidacion_masiva_{$resultado['periodo_inicio']}_{$resultado['periodo_fin']}.xlsx"
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La jornada laboral seleccionada no existe.',
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al exportar preliquidación masiva', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al exportar la preliquidación masiva.'], 500);
        }
    }

    private function preliquidacionPayload($preliquidacion): array
    {
        return [
            ...$preliquidacion->calculo_ajustado,
            'preliquidacion_uuid' => $preliquidacion->uuid,
            'estado_preliquidacion' => $preliquidacion->estado,
            'calculo_original' => $preliquidacion->calculo_original,
            'ajustes_revision' => $preliquidacion->ajustes,
            'generado_por' => $preliquidacion->generadoPor,
            'revisado_por' => $preliquidacion->revisadoPor,
            'aprobado_por' => $preliquidacion->aprobadoPor,
            'fecha_revision' => $preliquidacion->fecha_revision,
            'fecha_aprobacion' => $preliquidacion->fecha_aprobacion,
            'observacion_revision' => $preliquidacion->observacion_revision,
        ];
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
        $empresa = $empresaId ? Empresa::findOrFail($empresaId) : null;
        $empresaNombre = $empresa?->nombre ?? 'Todas las empresas';

        $nominas = Nomina::with([
            'empleado:id,name,email',
            'contratacion:id,tipo_documento,numero_documento,cargo,empresa_id',
            'jornadaLaboral:id,horas_semanales',
        ])
            ->where('liquidada', true)
            ->operativas()
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

        $empresaArchivo = Str::slug($empresaNombre, '_') ?: 'empresa';
        $filename = "nomina_liquidada_{$empresaArchivo}_{$inicio}_{$fin}.xlsx";

        return Excel::download(
            new NominaPlanoExport($nominas, $inicio, $fin, $empresaNombre),
            $filename
        );
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
        ])->operativas()->where('uuid', $uuid)->firstOrFail();

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
        ])->operativas()->where('uuid', $uuid)->firstOrFail();

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
