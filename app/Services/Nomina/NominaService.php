<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Comision;
use App\Models\Nomina\ConfiguracionNomina;
use App\Models\Nomina\Contratacion;
use App\Models\Nomina\Descuento;
use App\Models\Nomina\HoraExtra;
use App\Models\Nomina\Incapacidad;
use App\Models\Nomina\JornadaLaboral;
use App\Models\Nomina\LiquidacionRetiro;
use App\Models\Nomina\Nomina;
use App\Models\Nomina\NovedadRetroactiva;
use App\Models\Nomina\Permiso;
use App\Models\Nomina\PreliquidacionNomina;
use App\Models\Nomina\Valor;
use App\Models\Nomina\Vacacion;
use App\Models\Nomina\WorkSession;
use App\Models\User;
use App\EstadoEnum;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use LogicException;

class NominaService
{
    private const WITH = [
        'empleado.sede',
        'contratacion.empresa',
        'descuento',
        'jornadaLaboral',
        'novedadesRetroactivas',
        'transacionalRegistro',
        'liquidador:id,name,email',
        'reversor:id,name,email',
        'preliquidacion:id,uuid,estado,generado_por,revisado_por,aprobado_por',
    ];

    public function __construct(
        private readonly AjusteSalarialContratacionService $ajusteSalarialService,
        private readonly ConfiguracionNominaService $configuracionNominaService,
    ) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Nomina::with(self::WITH)
            ->where('liquidada', true)
            ->when(! empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(! empty($filters['jornada_laboral_id']), fn ($q) => $q->where('jornada_laboral_id', $filters['jornada_laboral_id']))
            ->when(! empty($filters['periodo_inicio']), fn ($q) => $q->whereDate('periodo_inicio', '>=', $filters['periodo_inicio']))
            ->when(! empty($filters['periodo_fin']), fn ($q) => $q->whereDate('periodo_fin', '<=', $filters['periodo_fin']))
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where(function ($query) use ($search) {
                    $query->whereHas('empleado', fn ($empleado) => $empleado->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('contratacion', fn ($contrato) => $contrato->where('cargo', 'like', "%{$search}%")
                            ->orWhere('numero_documento', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): Nomina
    {
        return Nomina::with(self::WITH)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function revertir(string $uuid, string $motivo, NominaPucPayloadService $payloadService): Nomina
    {
        return DB::transaction(function () use ($uuid, $motivo, $payloadService) {
            $nomina = Nomina::with(['comisiones', 'novedadesRetroactivas', 'preliquidacion'])
                ->where('uuid', $uuid)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($nomina->estado_contable, ['anulada', 'reversada'], true)) {
                throw new LogicException('Esta nómina ya fue anulada o reversada.');
            }

            if (LiquidacionRetiro::where('nomina_id', $nomina->id)->exists()) {
                throw new LogicException('La nómina pertenece a una liquidación definitiva y no puede reversarse por este flujo.');
            }

            $estadoAnterior = $nomina->estado_contable ?: 'pendiente';
            $requiereAsientoInverso = in_array($estadoAnterior, ['aprobado', 'exportado', 'cerrado'], true);
            $detalleReversion = [
                'tipo' => $requiereAsientoInverso ? 'reversion_contable' : 'anulacion_operativa',
                'estado_anterior' => $estadoAnterior,
                'motivo' => $motivo,
                'totales_originales' => [
                    'total_devengado' => (float) $nomina->total_devengado,
                    'total_deducciones' => (float) $nomina->total_deducciones,
                    'salario_neto' => (float) $nomina->salario_neto,
                    'costo_total_empleador' => (float) $nomina->costo_total_empleador,
                ],
                'asientos_inversos' => [],
            ];

            if ($requiereAsientoInverso) {
                $payload = $payloadService->generar($uuid);
                if (! $payload['valido']) {
                    throw new LogicException('No se puede generar el asiento inverso porque existen cuentas PUC sin configurar.');
                }

                $detalleReversion['asientos_inversos'] = $this->invertirAsientos(
                    $payload['contabilidad']['asientos'] ?? []
                );
            }

            $nomina->comisiones()->update([
                'status' => 'aprobada',
                'nomina_id' => null,
            ]);
            $nomina->novedadesRetroactivas()->update([
                'status' => 'aprobada',
                'nomina_id' => null,
            ]);

            if ($nomina->preliquidacion) {
                $nomina->preliquidacion->update([
                    'estado' => 'rechazada',
                    'observacion_revision' => "Nómina revertida: {$motivo}",
                ]);
            }

            $nomina->update([
                'estado_contable' => $requiereAsientoInverso ? 'reversada' : 'anulada',
                'motivo_reversion' => $motivo,
                'reversado_por' => Auth::id(),
                'fecha_reversion' => now(),
                'estado_contable_anterior' => $estadoAnterior,
                'detalle_reversion' => $detalleReversion,
            ]);

            Log::warning('Nómina revertida', [
                'uuid' => $nomina->uuid,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nomina->estado_contable,
                'reversado_por' => Auth::id(),
                'motivo' => $motivo,
            ]);

            return $nomina->fresh(self::WITH);
        });
    }

    private function invertirAsientos(array $asientos): array
    {
        return collect($asientos)->map(function (array $asiento) {
            $asiento['naturaleza_original'] = $asiento['naturaleza'] ?? null;
            $asiento['naturaleza'] = match ($asiento['naturaleza'] ?? null) {
                'debito' => 'credito',
                'credito' => 'debito',
                default => $asiento['naturaleza'] ?? null,
            };

            return $asiento;
        })->values()->all();
    }

    /**
     * Genera únicamente la nómina parcial asociada a una liquidación definitiva.
     * La nómina ordinaria debe persistirse desde una preliquidación aprobada.
     */
    public function liquidarNominaRetiro(array $data): Nomina
    {
        return DB::transaction(function () use ($data) {
            $this->validarPeriodoSinLiquidar($data['user_id'], $data['periodo_inicio'], $data['periodo_fin']);
            $calculo = $this->calcular($data);

            return $this->persistirCalculo($calculo);
        });
    }

    public function liquidarPreliquidacionAprobada(PreliquidacionNomina $preliquidacion): Nomina
    {
        return DB::transaction(function () use ($preliquidacion) {
            $preliquidacion = PreliquidacionNomina::query()
                ->lockForUpdate()
                ->findOrFail($preliquidacion->id);

            if ($preliquidacion->estado !== 'aprobada') {
                throw new LogicException('La nómina solo puede persistirse desde una preliquidación aprobada.');
            }

            $calculo = $preliquidacion->calculo_ajustado;
            $this->validarPeriodoSinLiquidar($calculo['user_id'], $calculo['periodo_inicio'], $calculo['periodo_fin']);

            return $this->persistirCalculo($calculo, $preliquidacion->id);
        });
    }

    private function persistirCalculo(array $calculo, ?int $preliquidacionId = null): Nomina
    {
        $nomina = Nomina::create([
                'user_id' => $calculo['user_id'],
                'jornada_laboral_id' => $calculo['jornada_laboral_id'],
                'contratacion_id' => $calculo['contratacion_id'],
                'preliquidacion_id' => $preliquidacionId,
                'descuento_id' => $calculo['descuento_id'],

                'periodo_inicio' => $calculo['periodo_inicio'],
                'periodo_fin' => $calculo['periodo_fin'],
                'dias_salario' => $calculo['dias_liquidados'],
                'dias_vacaciones_ordinarias' => $calculo['dias_vacaciones_ordinarias'],

                'horas_normales' => $calculo['horas_normales'],
                'horas_extras_nocturnas' => $calculo['horas_extras_nocturnas'],
                'horas_extras_diurnas' => $calculo['horas_extras_diurnas'],
                'horas_festivas' => $calculo['horas_festivas'],
                'horas_nocturnas_festivas' => $calculo['horas_nocturnas_festivas'],

                'valor_hora_normal' => $calculo['valor_hora_normal'],
                'valor_hora_nocturna' => $calculo['valor_hora_nocturna'],
                'valor_hora_dominical' => $calculo['valor_hora_dominical'],
                'valor_hora_dominical_extra' => $calculo['valor_hora_dominical_extra'],

                'salario_base_devengado' => $calculo['salario_base_devengado'],
                'auxilio_transporte' => $calculo['auxilio_transporte'],
                'pago_no_prestacional' => $calculo['pago_no_prestacional'],
                'total_comisiones' => $calculo['total_comisiones'],
                'total_novedades_retroactivas' => $calculo['total_novedades_retroactivas'],
                'detalle_novedades_retroactivas' => $calculo['detalle_novedades_retroactivas'],
                'valor_horas_normales' => $calculo['valor_horas_normales'],
                'valor_horas_extras_nocturnas' => $calculo['valor_horas_extras_nocturnas'],
                'valor_horas_extras_diurnas' => $calculo['valor_horas_extras_diurnas'],
                'valor_horas_festivas' => $calculo['valor_horas_festivas'],
                'valor_horas_nocturnas_festivas' => $calculo['valor_horas_nocturnas_festivas'],
                'total_devengado' => $calculo['total_devengado'],

                'deduccion_salud' => $calculo['deduccion_salud'],
                'deduccion_pension' => $calculo['deduccion_pension'],
                'total_descuentos_adicionales' => $calculo['total_descuentos_adicionales'],
                'total_deducciones' => $calculo['total_deducciones'],

                'base_aportes_empleador' => $calculo['base_aportes_empleador'],
                'porcentaje_salud_empleador' => $calculo['porcentaje_salud_empleador'],
                'porcentaje_pension_empleador' => $calculo['porcentaje_pension_empleador'],
                'porcentaje_arl' => $calculo['porcentaje_arl'],
                'porcentaje_sena' => $calculo['porcentaje_sena'],
                'porcentaje_icbf' => $calculo['porcentaje_icbf'],
                'porcentaje_caja_compensacion' => $calculo['porcentaje_caja_compensacion'],
                'costo_salud_empleador' => $calculo['costo_salud_empleador'],
                'costo_pension_empleador' => $calculo['costo_pension_empleador'],
                'costo_arl' => $calculo['costo_arl'],
                'costo_sena' => $calculo['costo_sena'],
                'costo_icbf' => $calculo['costo_icbf'],
                'costo_caja_compensacion' => $calculo['costo_caja_compensacion'],
                'costo_parafiscales' => $calculo['costo_parafiscales'],
                'costo_total_empleador' => $calculo['costo_total_empleador'],

                'salario_neto' => $calculo['salario_neto'],
                'liquidada' => true,
                'fecha_liquidacion' => now(),
                'liquidado_por' => Auth::id(),
            ]);

        Comision::whereIn('id', $calculo['comisiones_ids'] ?? [])->update([
                'status' => 'aplicada',
                'nomina_id' => $nomina->id,
            ]);

        NovedadRetroactiva::whereIn('id', $calculo['novedades_retroactivas_ids'] ?? [])->update([
                'status' => 'aplicada',
                'nomina_id' => $nomina->id,
            ]);

        Log::info('Nómina liquidada', [
                'uuid' => $nomina->uuid,
                'user_id' => $calculo['user_id'],
                'liquidado_por' => Auth::id(),
                'preliquidacion_id' => $preliquidacionId,
                'periodo' => $calculo['periodo_inicio'].' → '.$calculo['periodo_fin'],
                'total_devengado' => $calculo['total_devengado'],
                'total_deducciones' => $calculo['total_deducciones'],
                'salario_neto' => $calculo['salario_neto'],
            ]);

        return $nomina->load(self::WITH);
    }

    public function preliquidar(array $data): array
    {
        return $this->calcular($data);
    }

    /**
     * Calcula la preliquidación de todos los empleados activos con contrato vigente
     * para un período y jornada dados, sin persistir nada. Errores individuales
     * (contrato inexistente, período fuera de contrato, etc.) no detienen el lote.
     */
    public function preliquidarLote(array $data): array
    {
        $jornada = JornadaLaboral::where('status', true)->findOrFail($data['jornada_laboral_id']);

        $empleados = User::where('estado_id', EstadoEnum::ACTIVO->value)
            ->whereHas('contratacionActivaNomina')
            ->when(! empty($data['sede_id']), fn ($query) => $query->where('sede_id', $data['sede_id']))
            ->when(
                ! empty($data['empresa_id']),
                fn ($query) => $query->whereHas(
                    'contratacionActivaNomina',
                    fn ($contrato) => $contrato->where('empresa_id', $data['empresa_id'])
                )
            )
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $resultados = [];
        $errores = [];

        foreach ($empleados as $empleado) {
            try {
                $calculo = $this->preliquidar([
                    'user_id' => $empleado->id,
                    'periodo_inicio' => $data['periodo_inicio'],
                    'periodo_fin' => $data['periodo_fin'],
                    'jornada_laboral_id' => $jornada->id,
                    'descontar_tardanzas' => (bool) ($data['descontar_tardanzas'] ?? false),
                ]);
                $calculo['empleado'] = [
                    'id' => $empleado->id,
                    'name' => $empleado->name,
                    'email' => $empleado->email,
                ];
                $resultados[] = $calculo;
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                $errores[] = [
                    'user_id' => $empleado->id,
                    'empleado' => $empleado->name,
                    'message' => 'No se encontró contrato activo o configuración de tarifas para el empleado.',
                ];
            } catch (\LogicException $e) {
                $errores[] = [
                    'user_id' => $empleado->id,
                    'empleado' => $empleado->name,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $sumar = fn (string $campo) => round(array_sum(array_column($resultados, $campo)), 2);

        return [
            'periodo_inicio' => $data['periodo_inicio'],
            'periodo_fin' => $data['periodo_fin'],
            'jornada_laboral' => ['id' => $jornada->id, 'nombre' => $jornada->nombre],
            'descuenta_tardanzas' => (bool) ($data['descontar_tardanzas'] ?? false),
            'empleados' => $resultados,
            'errores' => $errores,
            'totales' => [
                'empleados_calculados' => count($resultados),
                'empleados_con_error' => count($errores),
                'horas_extras_diurnas' => $sumar('horas_extras_diurnas'),
                'horas_extras_nocturnas' => $sumar('horas_extras_nocturnas'),
                'horas_festivas' => $sumar('horas_festivas'),
                'horas_nocturnas_festivas' => $sumar('horas_nocturnas_festivas'),
                'valor_horas_extras_diurnas' => $sumar('valor_horas_extras_diurnas'),
                'valor_horas_extras_nocturnas' => $sumar('valor_horas_extras_nocturnas'),
                'valor_horas_festivas' => $sumar('valor_horas_festivas'),
                'valor_horas_nocturnas_festivas' => $sumar('valor_horas_nocturnas_festivas'),
                'minutos_tardanza' => $sumar('minutos_tardanza'),
                'valor_tardanzas' => $sumar('valor_tardanzas'),
                'minutos_permisos_no_remunerados' => $sumar('minutos_permisos_no_remunerados'),
                'valor_permisos_no_remunerados' => $sumar('valor_permisos_no_remunerados'),
                'total_devengado' => $sumar('total_devengado'),
                'total_deducciones' => $sumar('total_deducciones'),
                'salario_neto' => $sumar('salario_neto'),
            ],
        ];
    }

    public function getNominasPeriodoContable(string $periodoInicio, string $periodoFin): Collection
    {
        return Nomina::with(self::WITH)
            ->where('liquidada', true)
            ->operativas()
            ->whereDate('periodo_inicio', '>=', $periodoInicio)
            ->whereDate('periodo_fin', '<=', $periodoFin)
            ->orderBy('user_id')
            ->get();
    }

    public function aprobarContabilidad(string $uuid, NominaPucPayloadService $payloadService): Nomina
    {
        return DB::transaction(function () use ($uuid, $payloadService) {
            $nomina = $this->getByUuid($uuid);

            if (! $nomina->liquidada) {
                throw new LogicException('Solo se pueden aprobar nóminas liquidadas.');
            }

            if (in_array($nomina->estado_contable, ['aprobado', 'cerrado', 'exportado'], true)) {
                throw new LogicException('La nómina ya fue aprobada por contabilidad.');
            }

            $payload = $payloadService->generar($uuid);

            if (! $payload['valido']) {
                throw new LogicException('La nómina no se puede aprobar porque tiene cuentas PUC pendientes por configurar.');
            }

            $nomina->update([
                'estado_contable' => 'aprobado',
                'fecha_aprobacion_contable' => now(),
            ]);

            Log::info('Nómina aprobada por contabilidad', [
                'uuid' => $nomina->uuid,
                'user_id' => $nomina->user_id,
            ]);

            return $nomina->fresh(self::WITH);
        });
    }

    public function cerrarPeriodo(string $periodoInicio, string $periodoFin): int
    {
        return DB::transaction(function () use ($periodoInicio, $periodoFin) {
            $nominas = $this->getNominasPeriodoContable($periodoInicio, $periodoFin);

            if ($nominas->isEmpty()) {
                throw new LogicException('No hay nóminas liquidadas en el período seleccionado.');
            }

            $pendientes = $nominas->filter(fn ($nomina) => ! in_array($nomina->estado_contable, ['aprobado', 'cerrado', 'exportado'], true));
            if ($pendientes->isNotEmpty()) {
                throw new LogicException('No se puede cerrar el período porque hay nóminas sin aprobar en contabilidad.');
            }

            Nomina::whereIn('id', $nominas->pluck('id'))
                ->update([
                    'estado_contable' => 'cerrado',
                    'fecha_cierre_contable' => now(),
                ]);

            Log::info('Período de nómina cerrado en contabilidad', [
                'periodo_inicio' => $periodoInicio,
                'periodo_fin' => $periodoFin,
                'nominas' => $nominas->count(),
            ]);

            return $nominas->count();
        });
    }

    public function marcarPeriodoExportado(Collection $nominas): void
    {
        Nomina::whereIn('id', $nominas->pluck('id'))
            ->update([
                'estado_contable' => 'exportado',
            ]);
    }

    private function calcular(array $data): array
    {
        $userId = $data['user_id'];
        $inicio = Carbon::parse($data['periodo_inicio'])->startOfDay();
        $fin = Carbon::parse($data['periodo_fin'])->endOfDay();

        $contratacion = Contratacion::where('users_id', $userId)
            ->where('status', 1)
            ->latest('inicio_contratacion')
            ->firstOrFail();
        $configuracion = $this->configuracionNominaService->actual();
        $porcentajeIncapacidad = (float) $configuracion->porcentaje_incapacidad;
        $recargoExtraDiurna = (float) $configuracion->recargo_extra_diurna;
        $recargoExtraNocturna = (float) $configuracion->recargo_extra_nocturna;
        $recargoFestiva = (float) $configuracion->recargo_festiva;
        $recargoNocturnaFestiva = (float) $configuracion->recargo_nocturna_festiva;

        $inicioLiquidable = $inicio->copy()->max(Carbon::parse($contratacion->inicio_contratacion)->startOfDay());
        $finLiquidable = $fin->copy();
        if ($contratacion->fin_contrato) {
            $finLiquidable = $finLiquidable->min(Carbon::parse($contratacion->fin_contrato)->endOfDay());
        }

        if ($inicioLiquidable->gt($finLiquidable)) {
            throw new \LogicException('El contrato activo no cubre el período seleccionado.');
        }

        $baseSalarial = $this->ajusteSalarialService->salarioPromedioPeriodo(
            $contratacion,
            $inicioLiquidable,
            $finLiquidable->copy()->startOfDay()
        );

        $sessions = WorkSession::where('user_id', $userId)
            ->whereBetween('registro_diario', [$inicioLiquidable->toDateString(), $finLiquidable->toDateString()])
            ->get();
        $advertencias = [];

        if ($sessions->isEmpty()) {
            $advertencias[] = 'El empleado no tiene registros de ingreso/asistencia en el período liquidado.';
        }

        $jornada = JornadaLaboral::where('status', true)->findOrFail($data['jornada_laboral_id']);

        $totalMinutos = (int) $sessions->sum('minutos_trabajados');
        $festivoMinutos = (float) $sessions->sum('festivo_minutos');
        $sabadoMinutos = (float) $sessions->sum('sabado_minutos');
        $ordinariosMinutos = max(0, $totalMinutos - $festivoMinutos - $sabadoMinutos);

        $diasLiquidables = $this->diasComerciales($inicioLiquidable, $finLiquidable);
        $vacacionesOrdinarias = Vacacion::with('liquidacionPrestacion:id,vacacion_id,tipo')
            ->where('user_id', $userId)
            ->where('status', 'aprobada')
            ->where('tipo', 'ordinarias')
            ->whereDate('fecha_inicio', '<=', $finLiquidable->toDateString())
            ->whereDate('fecha_fin', '>=', $inicioLiquidable->toDateString())
            ->get();

        $vacacionSinLiquidar = $vacacionesOrdinarias->first(
            fn (Vacacion $vacacion) => $vacacion->liquidacionPrestacion === null
                || $vacacion->liquidacionPrestacion->tipo !== 'vacaciones_ordinarias'
        );

        if ($vacacionSinLiquidar) {
            throw new \LogicException(
                'La vacación ordinaria aprobada del '
                .$vacacionSinLiquidar->fecha_inicio->toDateString().' al '
                .$vacacionSinLiquidar->fecha_fin->toDateString()
                .' debe liquidarse por prestaciones antes de procesar esta nómina.'
            );
        }

        $diasVacacionesOrdinarias = (int) $vacacionesOrdinarias->sum(
            fn (Vacacion $vacacion) => $this->diasVacacionEnPeriodo(
                $vacacion,
                $inicioLiquidable,
                $finLiquidable
            )
        );
        $diasSalario = max(0, $diasLiquidables - $diasVacacionesOrdinarias);
        $horasMensualesJornada = $this->horasMensualesJornada($jornada);
        $horasEsperadasPeriodo = round($horasMensualesJornada * ($diasSalario / 30), 2);
        $minutosEsperados = $horasEsperadasPeriodo * 60;
        $horasNormales = round(min($ordinariosMinutos, $minutosEsperados) / 60, 2);
        $minutosExtrasDetectados = max(0, $ordinariosMinutos - $minutosEsperados);
        $minutosNocturnosTrabajados = $this->minutosNocturnosTrabajados($sessions, $configuracion);
        $minutosExtrasNocturnosDetectados = min($minutosExtrasDetectados, $minutosNocturnosTrabajados);
        $minutosExtrasDiurnosDetectados = max(0, $minutosExtrasDetectados - $minutosExtrasNocturnosDetectados);
        $horasExtrasDiurnasDetectadas = round($minutosExtrasDiurnosDetectados / 60, 2);
        $horasExtrasNocturnasDetectadas = round($minutosExtrasNocturnosDetectados / 60, 2);
        $minutosFestivosDetectados = (int) round($festivoMinutos + $sabadoMinutos);
        $minutosNocturnosFestivosDetectados = $this->minutosNocturnosFestivosTrabajados($sessions, $configuracion);
        $minutosFestivosDiurnosDetectados = max(0, $minutosFestivosDetectados - $minutosNocturnosFestivosDetectados);
        $horasFestivasDetectadas = round($minutosFestivosDiurnosDetectados / 60, 2);
        $horasNocturnasFestivasDetectadas = round($minutosNocturnosFestivosDetectados / 60, 2);
        $horasFestivasTotal = $horasFestivasDetectadas;

        $extrasAprobadas = HoraExtra::where('user_id', $userId)
            ->whereBetween('fecha', [$inicioLiquidable->toDateString(), $finLiquidable->toDateString()])
            ->where('status', 'aprobada')
            ->get();

        $minExtDiurnosAprobados   = 0;
        $minExtNocturnosAprobados = 0;
        $minNocturnosFestivos     = 0;
        $minFestivos              = 0;

        foreach ($extrasAprobadas as $extra) {
            $fechaExtra       = Carbon::parse($extra->fecha);
            $inicioExtra = Carbon::parse(
                $extra->fecha->toDateString().' '.($extra->hora_inicio ?: $jornada->hora_salida)
            );
            $finExtra = $extra->hora_fin
                ? Carbon::parse($extra->fecha->toDateString().' '.$extra->hora_fin)
                : $inicioExtra->copy()->addMinutes((int) round((float) $extra->horas * 60));
            if ($finExtra->lessThanOrEqualTo($inicioExtra)) {
                $finExtra->addDay();
            }
            $totalMin         = (int) round((float) $extra->horas * 60);
            $minutosNocturnos = $this->minutosNocturnosEntre($inicioExtra, $finExtra, $configuracion);
            $minutosDiurnos   = max(0, $totalMin - $minutosNocturnos);
            $esFestivo        = $fechaExtra->isSunday()
                || WorkSession::where('user_id', $extra->user_id)
                    ->whereDate('registro_diario', $fechaExtra->toDateString())
                    ->where('festivo_minutos', '>', 0)
                    ->exists();

            if ($esFestivo) {
                $minFestivos          += $minutosDiurnos;
                $minNocturnosFestivos += $minutosNocturnos;
            } else {
                $minExtDiurnosAprobados   += $minutosDiurnos;
                $minExtNocturnosAprobados += $minutosNocturnos;
            }
        }

        $horasExtrasDiurnasAprobadas   = round($minExtDiurnosAprobados / 60, 2);
        $horasExtrasNocturnasAprobadas = round($minExtNocturnosAprobados / 60, 2);
        $horasFestivasAprobadas        = round($minFestivos / 60, 2);
        $horasNocturnasFestivasAprobadas = round($minNocturnosFestivos / 60, 2);
        $horasExtrasDiurnas            = min($horasExtrasDiurnasAprobadas, $horasExtrasDiurnasDetectadas);
        $horasExtrasNocturnas          = min($horasExtrasNocturnasAprobadas, $horasExtrasNocturnasDetectadas);
        $horasNocturnasFestivas        = min($horasNocturnasFestivasAprobadas, $horasNocturnasFestivasDetectadas);
        $horasFestivasTotal            = min($horasFestivasAprobadas, $horasFestivasDetectadas);

        if ($horasExtrasDiurnasDetectadas > $horasExtrasDiurnasAprobadas) {
            $advertencias[] = "Se detectaron {$horasExtrasDiurnasDetectadas} horas extra diurnas desde asistencia por exceder la jornada del período.";
        }

        if ($horasExtrasNocturnasDetectadas > $horasExtrasNocturnasAprobadas) {
            $advertencias[] = "Se detectaron {$horasExtrasNocturnasDetectadas} horas extra nocturnas desde asistencia por exceder la jornada después de las 7:00 p. m.";
        }

        if ($horasExtrasDiurnasAprobadas > $horasExtrasDiurnasDetectadas) {
            $advertencias[] = "Hay {$horasExtrasDiurnasAprobadas} horas extra diurnas autorizadas, pero solo {$horasExtrasDiurnasDetectadas} fueron trabajadas.";
        }

        if ($horasExtrasNocturnasAprobadas > $horasExtrasNocturnasDetectadas) {
            $advertencias[] = "Hay {$horasExtrasNocturnasAprobadas} horas extra nocturnas autorizadas, pero solo {$horasExtrasNocturnasDetectadas} fueron trabajadas.";
        }

        if ($horasFestivasDetectadas > $horasFestivasAprobadas) {
            $advertencias[] = "Se detectaron {$horasFestivasDetectadas} horas festivas diurnas, pero solo {$horasFestivasAprobadas} estan autorizadas para pago.";
        }

        if ($horasFestivasAprobadas > $horasFestivasDetectadas) {
            $advertencias[] = "Hay {$horasFestivasAprobadas} horas festivas diurnas autorizadas, pero solo {$horasFestivasDetectadas} fueron trabajadas.";
        }

        if ($horasNocturnasFestivasDetectadas > $horasNocturnasFestivasAprobadas) {
            $advertencias[] = "Se detectaron {$horasNocturnasFestivasDetectadas} horas festivas nocturnas, pero solo {$horasNocturnasFestivasAprobadas} estan autorizadas para pago.";
        }

        if ($horasNocturnasFestivasAprobadas > $horasNocturnasFestivasDetectadas) {
            $advertencias[] = "Hay {$horasNocturnasFestivasAprobadas} horas festivas nocturnas autorizadas, pero solo {$horasNocturnasFestivasDetectadas} fueron trabajadas.";
        }

        $salarioMensual = (float) $baseSalarial['salario_mensual'];
        $valorDia = round($salarioMensual / 30, 6);
        $valorConfigurado = Valor::where('status', true)->latest()->first();
        $valorHoraCalculado = round($salarioMensual / $horasMensualesJornada, 2);
        $valorHoraBase = max($valorHoraCalculado, (float) ($valorConfigurado?->valor_hora_normal ?? 0));
        $valorHoraNocturna = max(
            round($valorHoraBase * (1 + $recargoExtraNocturna), 2),
            (float) ($valorConfigurado?->valor_hora_nocturna ?? 0)
        );
        $valorHoraDominical = max(
            round($valorHoraBase * (1 + $recargoFestiva), 2),
            (float) ($valorConfigurado?->valor_hora_dominical ?? 0)
        );
        $valorHoraDominicalExtra = max(
            round($valorHoraBase * (1 + $recargoNocturnaFestiva), 2),
            (float) ($valorConfigurado?->valor_hora_dominical_extra ?? 0)
        );

        $diasIncapacidad = $this->contarDiasNovedad(
            Incapacidad::where('user_id', $userId)
                ->where('estado_revision', 'aprobada')
                ->whereDate('inicio', '<=', $finLiquidable->toDateString())
                ->whereDate('fin', '>=', $inicioLiquidable->toDateString())
                ->get(),
            $inicioLiquidable,
            $finLiquidable,
            'inicio',
            'fin'
        );

        // Las vacaciones compensadas se pagan exclusivamente desde liquidación
        // de prestaciones. Sumarlas aquí produciría un doble pago.
        $diasVacacionesCompensadas = 0;

        $permisosNoRemunerados = Permiso::where('user_id', $userId)
            ->whereBetween('fecha', [$inicioLiquidable->toDateString(), $finLiquidable->toDateString()])
            ->where('status', 'aprobado')
            ->where('es_remunerado', false)
            ->get();

        $minutosNoRemunerados = $permisosNoRemunerados->sum(fn ($p) => Carbon::parse($p->hora_inicio)->diffInMinutes(Carbon::parse($p->hora_fin))
        );

        $valorPermisosNoRemunerados = round(($minutosNoRemunerados / 60) * $valorHoraBase, 2);
        $minutosTardanza = (int) $sessions->sum('minutos_tardanza');
        $valorTardanzas = round(($minutosTardanza / 60) * $valorHoraBase, 2);
        $descontarTardanzas = (bool) ($data['descontar_tardanzas'] ?? false);
        $salarioBaseSinIncapacidad = round($valorDia * $diasSalario, 2);
        $valorIncapacidadReconocido = round($valorDia * $diasIncapacidad * $porcentajeIncapacidad, 2);
        $deduccionIncapacidad = round($valorDia * $diasIncapacidad * (1 - $porcentajeIncapacidad), 2);
        $salarioBasePeriodo = round(($valorDia * max(0, $diasSalario - $diasIncapacidad))
            + $valorIncapacidadReconocido, 2);
        $auxilioTransportePeriodo = round((float) $baseSalarial['auxilio_transporte'] * ($diasSalario / 30), 2);
        $pagoNoPrestacionalPeriodo = round((float) $baseSalarial['no_salarial'] * ($diasSalario / 30), 2);
        $comisiones = Comision::where('user_id', $userId)
            ->where('status', 'aprobada')
            ->whereDate('periodo_inicio', $inicio->toDateString())
            ->whereDate('periodo_fin', $fin->toDateString())
            ->get();
        $totalComisiones = round((float) $comisiones->sum('valor'), 2);
        $novedadesRetroactivas = $this->calcularNovedadesRetroactivas($userId, $inicioLiquidable, $finLiquidable);

        // El salario mensual ya remunera las horas ordinarias; se guardan para control, no se suman otra vez.
        $valorHorasNormales = 0;
        $valorHorasExtrasDiurnas = round($horasExtrasDiurnas * $valorHoraBase * (1 + $recargoExtraDiurna), 2);
        $valorHorasExtrasNocturnas = round($horasExtrasNocturnas * $valorHoraBase * (1 + $recargoExtraNocturna), 2);
        $valorHorasFestivas = round($horasFestivasTotal * $valorHoraBase * (1 + $recargoFestiva), 2);
        $valorHorasNocturnasFestivas = round($horasNocturnasFestivas * $valorHoraBase * (1 + $recargoNocturnaFestiva), 2);

        $totalDevengado = $salarioBasePeriodo
            + $auxilioTransportePeriodo
            + $pagoNoPrestacionalPeriodo
            + $totalComisiones
            + $novedadesRetroactivas['devengos']
            + $valorHorasNormales
            + $valorHorasExtrasDiurnas
            + $valorHorasExtrasNocturnas
            + $valorHorasFestivas
            + $valorHorasNocturnasFestivas;

        $baseParaDeducciones = $salarioBasePeriodo
            + $totalComisiones
            + $novedadesRetroactivas['devengos']
            + $valorHorasNormales
            + $valorHorasExtrasDiurnas
            + $valorHorasExtrasNocturnas
            + $valorHorasFestivas
            + $valorHorasNocturnasFestivas;

        $aplicaSalud = (bool) ($contratacion->aplica_salud ?? true);
        $aplicaPension = (bool) ($contratacion->aplica_pension ?? true);
        $aplicaArl = (bool) ($contratacion->aplica_arl ?? true);
        $aplicaSena = (bool) ($contratacion->aplica_sena ?? true);
        $aplicaIcbf = (bool) ($contratacion->aplica_icbf ?? true);
        $aplicaCajaCompensacion = (bool) ($contratacion->aplica_caja_compensacion ?? true);

        $porcentajeSaludEmpleado = $aplicaSalud ? (float) $configuracion->porcentaje_salud_empleado : 0.0;
        $porcentajePensionEmpleado = $aplicaPension ? (float) $configuracion->porcentaje_pension_empleado : 0.0;
        $porcentajeSalud = $porcentajeSaludEmpleado / 100;
        $porcentajePension = $porcentajePensionEmpleado / 100;
        $deduccionSalud = round($baseParaDeducciones * $porcentajeSalud, 2);
        $deduccionPension = round($baseParaDeducciones * $porcentajePension, 2);
        $descuentosNomina = $this->calcularDescuentos($userId, $inicioLiquidable, $finLiquidable, $data['descuento_id'] ?? null);
        $totalDescuentosAdicionales = round(
            $descuentosNomina['valor']
            + $valorPermisosNoRemunerados
            + $novedadesRetroactivas['deducciones']
            + ($descontarTardanzas ? $valorTardanzas : 0),
            2
        );
        $totalDeducciones = round($deduccionSalud + $deduccionPension + $totalDescuentosAdicionales, 2);
        $salarioNeto = round($totalDevengado - $totalDeducciones, 2);
        $baseAportesEmpleador = round($baseParaDeducciones, 2);
        $porcentajeSaludEmpleador = $aplicaSalud ? (float) $configuracion->porcentaje_salud_empleador : 0.0;
        $porcentajePensionEmpleador = $aplicaPension ? (float) $configuracion->porcentaje_pension_empleador : 0.0;
        $porcentajeArl = $aplicaArl ? (float) $configuracion->porcentaje_arl : 0.0;
        $porcentajeSena = $aplicaSena ? (float) $configuracion->porcentaje_sena : 0.0;
        $porcentajeIcbf = $aplicaIcbf ? (float) $configuracion->porcentaje_icbf : 0.0;
        $porcentajeCajaCompensacion = $aplicaCajaCompensacion ? (float) $configuracion->porcentaje_caja_compensacion : 0.0;
        $costoSaludEmpleador = round($baseAportesEmpleador * ($porcentajeSaludEmpleador / 100), 2);
        $costoPensionEmpleador = round($baseAportesEmpleador * ($porcentajePensionEmpleador / 100), 2);
        $costoArl = round($baseAportesEmpleador * ($porcentajeArl / 100), 2);
        $costoSena = round($baseAportesEmpleador * ($porcentajeSena / 100), 2);
        $costoIcbf = round($baseAportesEmpleador * ($porcentajeIcbf / 100), 2);
        $costoCajaCompensacion = round($baseAportesEmpleador * ($porcentajeCajaCompensacion / 100), 2);
        $costoParafiscales = round($costoSena + $costoIcbf + $costoCajaCompensacion, 2);
        $costoTotalEmpleador = round(
            $totalDevengado
            + $costoSaludEmpleador
            + $costoPensionEmpleador
            + $costoArl
            + $costoParafiscales,
            2
        );

        return [
            'user_id' => $userId,
            'contratacion_id' => $contratacion->id,
            'jornada_laboral_id' => $jornada->id,
            'descuento_id' => $descuentosNomina['descuento_id'],
            'periodo_inicio' => $inicio->toDateString(),
            'periodo_fin' => $fin->toDateString(),
            'dias_liquidados' => $diasSalario,
            'dias_periodo' => $diasLiquidables,
            'dias_vacaciones_ordinarias' => $diasVacacionesOrdinarias,
            'horas_semanales_jornada' => (int) $jornada->horas_semanales,
            'horas_mensuales_jornada' => $horasMensualesJornada,
            'horas_esperadas_periodo' => $horasEsperadasPeriodo,
            'horas_trabajadas_periodo' => round($ordinariosMinutos / 60, 2),
            'dias_incapacidad' => $diasIncapacidad,
            'salario_base_sin_incapacidad' => $salarioBaseSinIncapacidad,
            'valor_incapacidad_reconocido' => $valorIncapacidadReconocido,
            'deduccion_incapacidad' => $deduccionIncapacidad,
            'dias_vacaciones_compensadas' => $diasVacacionesCompensadas,
            'minutos_permisos_no_remunerados' => $minutosNoRemunerados,
            'valor_permisos_no_remunerados' => $valorPermisosNoRemunerados,
            'minutos_tardanza' => $minutosTardanza,
            'valor_tardanzas' => $valorTardanzas,
            'descuenta_tardanzas' => $descontarTardanzas,
            'horas_normales' => $horasNormales,
            'horas_extras_diurnas_detectadas' => $horasExtrasDiurnasDetectadas,
            'horas_extras_diurnas_aprobadas' => $horasExtrasDiurnasAprobadas,
            'horas_extras_nocturnas_detectadas' => $horasExtrasNocturnasDetectadas,
            'horas_extras_nocturnas_aprobadas' => $horasExtrasNocturnasAprobadas,
            'horas_extras_nocturnas' => $horasExtrasNocturnas,
            'horas_extras_diurnas' => $horasExtrasDiurnas,
            'horas_festivas' => $horasFestivasTotal,
            'horas_nocturnas_festivas' => $horasNocturnasFestivas,
            'valor_hora_normal' => $valorHoraBase,
            'valor_hora_nocturna' => $valorHoraNocturna,
            'valor_hora_dominical' => $valorHoraDominical,
            'valor_hora_dominical_extra' => $valorHoraDominicalExtra,
            'salario_base_devengado' => $salarioBasePeriodo,
            'detalle_salario_vigente' => $baseSalarial['tramos'] ?? [],
            'auxilio_transporte' => $auxilioTransportePeriodo,
            'pago_no_prestacional' => $pagoNoPrestacionalPeriodo,
            'total_comisiones' => $totalComisiones,
            'total_novedades_retroactivas' => $novedadesRetroactivas['total'],
            'detalle_comisiones' => $comisiones->map(fn ($comision) => [
                'uuid' => $comision->uuid,
                'concepto' => $comision->concepto,
                'valor' => $comision->valor,
            ])->values(),
            'comisiones_ids' => $comisiones->pluck('id')->all(),
            'detalle_novedades_retroactivas' => $novedadesRetroactivas['detalle'],
            'novedades_retroactivas_ids' => $novedadesRetroactivas['ids'],
            'valor_horas_normales' => $valorHorasNormales,
            'valor_horas_extras_nocturnas' => $valorHorasExtrasNocturnas,
            'valor_horas_extras_diurnas' => $valorHorasExtrasDiurnas,
            'valor_horas_festivas' => $valorHorasFestivas,
            'valor_horas_nocturnas_festivas' => $valorHorasNocturnasFestivas,
            'total_devengado' => round($totalDevengado, 2),
            'deduccion_salud' => $deduccionSalud,
            'deduccion_pension' => $deduccionPension,
            'porcentaje_salud_empleado' => $porcentajeSaludEmpleado,
            'porcentaje_pension_empleado' => $porcentajePensionEmpleado,
            'aplica_salud' => $aplicaSalud,
            'aplica_pension' => $aplicaPension,
            'aplica_arl' => $aplicaArl,
            'aplica_sena' => $aplicaSena,
            'aplica_icbf' => $aplicaIcbf,
            'aplica_caja_compensacion' => $aplicaCajaCompensacion,
            'recargo_extra_diurna' => $recargoExtraDiurna,
            'recargo_extra_nocturna' => $recargoExtraNocturna,
            'recargo_festiva' => $recargoFestiva,
            'recargo_nocturna_festiva' => $recargoNocturnaFestiva,
            'porcentaje_incapacidad' => $porcentajeIncapacidad,
            'hora_inicio_nocturna' => $this->horaConfiguracion($configuracion, 'hora_inicio_nocturna'),
            'hora_fin_nocturna' => $this->horaConfiguracion($configuracion, 'hora_fin_nocturna'),
            'total_descuentos_adicionales' => $totalDescuentosAdicionales,
            'total_deducciones' => $totalDeducciones,
            'base_aportes_empleador' => $baseAportesEmpleador,
            'porcentaje_salud_empleador' => $porcentajeSaludEmpleador,
            'porcentaje_pension_empleador' => $porcentajePensionEmpleador,
            'porcentaje_arl' => $porcentajeArl,
            'porcentaje_sena' => $porcentajeSena,
            'porcentaje_icbf' => $porcentajeIcbf,
            'porcentaje_caja_compensacion' => $porcentajeCajaCompensacion,
            'costo_salud_empleador' => $costoSaludEmpleador,
            'costo_pension_empleador' => $costoPensionEmpleador,
            'costo_arl' => $costoArl,
            'costo_sena' => $costoSena,
            'costo_icbf' => $costoIcbf,
            'costo_caja_compensacion' => $costoCajaCompensacion,
            'costo_parafiscales' => $costoParafiscales,
            'costo_total_empleador' => $costoTotalEmpleador,
            'salario_neto' => $salarioNeto,
            'detalle_descuentos' => $descuentosNomina['detalle'],
            'advertencias' => $advertencias,
        ];
    }

    private function validarPeriodoSinLiquidar(int $userId, string $periodoInicio, string $periodoFin): void
    {
        $existe = Nomina::where('user_id', $userId)
            ->whereDate('periodo_inicio', '<=', $periodoFin)
            ->whereDate('periodo_fin', '>=', $periodoInicio)
            ->where('liquidada', true)
            ->operativas()
            ->exists();

        if ($existe) {
            throw new \LogicException('Este empleado ya tiene una nómina liquidada que se cruza con el período seleccionado.');
        }
    }

    private function calcularNovedadesRetroactivas(int $userId, Carbon $inicio, Carbon $fin): array
    {
        $novedades = NovedadRetroactiva::where('user_id', $userId)
            ->where('status', 'aprobada')
            ->whereDate('aplicar_desde', '<=', $fin->toDateString())
            ->where(function ($query) use ($inicio) {
                $query->whereNull('aplicar_hasta')
                    ->orWhereDate('aplicar_hasta', '>=', $inicio->toDateString());
            })
            ->get();

        $devengos = round((float) $novedades->where('tipo', 'devengo')->sum('valor'), 2);
        $deducciones = round((float) $novedades->where('tipo', 'deduccion')->sum('valor'), 2);

        return [
            'devengos' => $devengos,
            'deducciones' => $deducciones,
            'total' => round($devengos - $deducciones, 2),
            'ids' => $novedades->pluck('id')->all(),
            'detalle' => $novedades->map(fn ($novedad) => [
                'uuid' => $novedad->uuid,
                'tipo' => $novedad->tipo,
                'concepto' => $novedad->concepto,
                'fecha_origen' => $novedad->fecha_origen?->toDateString(),
                'aplicar_desde' => $novedad->aplicar_desde?->toDateString(),
                'aplicar_hasta' => $novedad->aplicar_hasta?->toDateString(),
                'valor' => (float) $novedad->valor,
            ])->values()->all(),
        ];
    }

    private function calcularDescuentos(int $userId, Carbon $inicio, Carbon $fin, ?int $descuentoId = null): array
    {
        $query = Descuento::where('user_id', $userId)
            ->where('status', true)
            ->whereDate('inicio', '<=', $fin->toDateString())
            ->where(function ($q) use ($inicio) {
                $q->whereNull('fin')
                    ->orWhereDate('fin', '>=', $inicio->toDateString());
            });

        if ($descuentoId) {
            $query->where('id', $descuentoId);
        }

        $descuentos = $query->get();
        $detalle = [];
        $total = 0;

        foreach ($descuentos as $descuento) {
            $valorCuota = (float) ($descuento->valor_cuota ?: $descuento->monto);
            $cantidadCuotas = $this->cantidadCuotasDescuento($descuento, $inicio, $fin);
            $valorAplicado = round($valorCuota * $cantidadCuotas, 2);
            $total += $valorAplicado;
            $detalle[] = [
                'id' => $descuento->id,
                'concepto' => $descuento->concepto_descuento,
                'cuotas' => $cantidadCuotas,
                'valor_cuota' => round($valorCuota, 2),
                'valor' => $valorAplicado,
            ];
        }

        return [
            'valor' => round($total, 2),
            'descuento_id' => $descuentos->count() === 1 ? $descuentos->first()->id : $descuentoId,
            'detalle' => $detalle,
        ];
    }

    private function contarDiasNovedad($items, Carbon $inicio, Carbon $fin, string $campoInicio, string $campoFin): int
    {
        $dias = [];

        foreach ($items as $item) {
            $desde = Carbon::parse($item->{$campoInicio})->startOfDay()->max($inicio->copy());
            $hasta = Carbon::parse($item->{$campoFin})->endOfDay()->min($fin->copy());

            while ($desde->lte($hasta)) {
                $dias[$desde->toDateString()] = true;
                $desde->addDay();
            }
        }

        return count($dias);
    }

    private function cantidadCuotasDescuento(Descuento $descuento, Carbon $inicio, Carbon $fin): int
    {
        if ($descuento->frecuencia_pago !== 'quincenal') {
            return 1;
        }

        $diasPeriodo = $this->diasComerciales($inicio, $fin);

        return $diasPeriodo > 15 ? 2 : 1;
    }

    private function minutosNocturnosTrabajados($sessions, ConfiguracionNomina $configuracion): int
    {
        $total = 0;

        foreach ($sessions as $session) {
            if (! $session->hora_entrada || ! $session->hora_salida) {
                continue;
            }

            $entrada = Carbon::parse($session->hora_entrada);
            $salida = Carbon::parse($session->hora_salida);
            if ($salida->lessThanOrEqualTo($entrada)) {
                $salida->addDay();
            }

            $minutos = $this->minutosNocturnosEntre($entrada, $salida, $configuracion);
            $minutos -= $this->minutosNocturnosDeDescanso($session->hora_salida_brake, $session->hora_ingreso_brake, $configuracion);
            $minutos -= $this->minutosNocturnosDeDescanso($session->hora_salida_almuerzo, $session->hora_ingreso_almuerzo, $configuracion);

            $total += max(0, $minutos);
        }

        return max(0, $total);
    }

    private function minutosNocturnosFestivosTrabajados($sessions, ConfiguracionNomina $configuracion): int
    {
        $total = 0;

        foreach ($sessions as $session) {
            if (! $session->hora_entrada || ! $session->hora_salida) {
                continue;
            }

            $minutosFestivosSesion = (int) round((float) ($session->festivo_minutos ?? 0) + (float) ($session->sabado_minutos ?? 0));
            if ($minutosFestivosSesion <= 0) {
                continue;
            }

            $entrada = Carbon::parse($session->hora_entrada);
            $salida = Carbon::parse($session->hora_salida);
            if ($salida->lessThanOrEqualTo($entrada)) {
                $salida->addDay();
            }

            $minutosNocturnos = $this->minutosNocturnosEntre($entrada, $salida, $configuracion);
            $minutosNocturnos -= $this->minutosNocturnosDeDescanso($session->hora_salida_brake, $session->hora_ingreso_brake, $configuracion);
            $minutosNocturnos -= $this->minutosNocturnosDeDescanso($session->hora_salida_almuerzo, $session->hora_ingreso_almuerzo, $configuracion);

            $total += min($minutosFestivosSesion, max(0, $minutosNocturnos));
        }

        return max(0, $total);
    }

    private function minutosNocturnosDeDescanso($inicio, $fin, ConfiguracionNomina $configuracion): int
    {
        if (! $inicio || ! $fin) {
            return 0;
        }

        $desde = Carbon::parse($inicio);
        $hasta = Carbon::parse($fin);
        if ($hasta->lessThanOrEqualTo($desde)) {
            $hasta->addDay();
        }

        return $this->minutosNocturnosEntre($desde, $hasta, $configuracion);
    }

    private function minutosNocturnosEntre(Carbon $inicio, Carbon $fin, ConfiguracionNomina $configuracion): int
    {
        $total = 0;
        $cursor = $inicio->copy()->startOfDay();
        $horaInicioNocturna = $this->horaConfiguracion($configuracion, 'hora_inicio_nocturna');
        $horaFinNocturna = $this->horaConfiguracion($configuracion, 'hora_fin_nocturna');

        while ($cursor->lte($fin)) {
            $inicioNoche = Carbon::parse($cursor->toDateString().' '.$horaInicioNocturna);
            $finNoche = Carbon::parse($cursor->copy()->addDay()->toDateString().' '.$horaFinNocturna);
            $desde = $inicio->copy()->max($inicioNoche);
            $hasta = $fin->copy()->min($finNoche);

            if ($desde->lt($hasta)) {
                $total += (int) $desde->diffInMinutes($hasta);
            }

            $cursor->addDay();
        }

        return $total;
    }

    private function horaConfiguracion(ConfiguracionNomina $configuracion, string $campo): string
    {
        return Carbon::parse($configuracion->{$campo})->format('H:i:s');
    }

    private function horasMensualesJornada(JornadaLaboral $jornada): float
    {
        return max(1, (float) $jornada->horas_semanales * 5);
    }

    private function diasComerciales(Carbon $inicio, Carbon $fin): int
    {
        $inicio = $inicio->copy()->startOfDay();
        $fin = $fin->copy()->startOfDay();

        if ($inicio->isSameMonth($fin)) {
            return min(30, $this->diasComercialesMes($inicio, $fin));
        }

        $dias = $this->diasComercialesMes($inicio, $inicio->copy()->endOfMonth()->startOfDay());
        $cursor = $inicio->copy()->addMonthNoOverflow()->startOfMonth();

        while ($cursor->lt($fin->copy()->startOfMonth())) {
            $dias += 30;
            $cursor->addMonthNoOverflow();
        }

        $dias += $this->diasComercialesMes($fin->copy()->startOfMonth(), $fin);

        return max(1, $dias);
    }

    private function diasVacacionEnPeriodo(
        Vacacion $vacacion,
        Carbon $inicioPeriodo,
        Carbon $finPeriodo
    ): int {
        $inicioVacacion = Carbon::parse($vacacion->fecha_inicio)->startOfDay();
        $finVacacion = Carbon::parse($vacacion->fecha_fin)->startOfDay();
        $inicioPeriodo = $inicioPeriodo->copy()->startOfDay();
        $finPeriodo = $finPeriodo->copy()->startOfDay();
        $inicioCruce = $inicioVacacion->copy()->max($inicioPeriodo);
        $finCruce = $finVacacion->copy()->min($finPeriodo);

        if ($inicioCruce->gt($finCruce)) {
            return 0;
        }

        // Para nómina se excluyen los días calendario comerciales cubiertos por
        // el descanso. Los días hábiles aprobados determinan el pago separado.
        return $this->diasComerciales($inicioCruce, $finCruce);
    }

    private function diasComercialesMes(Carbon $inicio, Carbon $fin): int
    {
        $ultimoDiaMes = $fin->copy()->endOfMonth()->day;
        $diaInicio = min($inicio->day, 30);
        $diaFin = $fin->day === $ultimoDiaMes ? 30 : min($fin->day, 30);

        return max(1, $diaFin - $diaInicio + 1);
    }
}
