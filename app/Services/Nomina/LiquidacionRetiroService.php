<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Comision;
use App\Models\Nomina\ConfiguracionNomina;
use App\Models\Nomina\Contratacion;
use App\Models\Nomina\LiquidacionRetiro;
use App\Models\Nomina\Nomina;
use App\Models\Nomina\Vacacion;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiquidacionRetiroService
{
    private const WITH = [
        'empleado:id,name,email',
        'contratacion.empresa',
        'contratacion.tipoContrato',
        'nomina',
        'jornadaLaboral',
        'comisiones',
    ];

    public function __construct(
        private readonly NominaService $nominaService,
        private readonly AjusteSalarialContratacionService $ajusteSalarialService
    ) {}

    public function getByUuid(string $uuid): LiquidacionRetiro
    {
        return LiquidacionRetiro::with(self::WITH)->where('uuid', $uuid)->firstOrFail();
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return LiquidacionRetiro::with(self::WITH)
            ->when(! empty($filters['user_id']), fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when(! empty($filters['fecha_desde']), fn ($query) => $query->whereDate('fecha_retiro', '>=', $filters['fecha_desde']))
            ->when(! empty($filters['fecha_hasta']), fn ($query) => $query->whereDate('fecha_retiro', '<=', $filters['fecha_hasta']))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('motivo_retiro', 'like', "%{$search}%")
                        ->orWhereHas('empleado', fn ($empleado) => $empleado
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('contratacion', fn ($contrato) => $contrato
                            ->where('numero_documento', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('fecha_retiro')
            ->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));
    }

    public function preliquidar(array $data): array
    {
        return $this->respuestaPublica($this->calcular($data));
    }

    public function liquidar(array $data): LiquidacionRetiro
    {
        return DB::transaction(function () use ($data) {
            $calculo = $this->calcular($data);
            $contratacion = Contratacion::lockForUpdate()->findOrFail($calculo['contratacion_id']);

            if (LiquidacionRetiro::where('contratacion_id', $contratacion->id)->exists()) {
                throw new \LogicException('Este contrato ya tiene una liquidación definitiva.');
            }

            $nomina = null;
            if ($calculo['_nomina_payload']) {
                $nomina = $this->nominaService->liquidar($calculo['_nomina_payload']);
            }

            $liquidacion = LiquidacionRetiro::create([
                ...$this->datosPersistencia($calculo),
                'nomina_id' => $nomina?->id,
                'detalle_calculo' => $this->respuestaPublica($calculo),
                'fecha_liquidacion' => now(),
            ]);

            Comision::whereIn('id', $calculo['_comisiones_pendientes_ids'])
                ->where('status', 'aprobada')
                ->update([
                    'status' => 'aplicada',
                    'liquidacion_retiro_id' => $liquidacion->id,
                ]);

            $contratacion->update([
                'fin_contrato' => Carbon::parse($calculo['fecha_retiro'])->endOfDay(),
                'status' => false,
            ]);

            Log::info('Liquidación definitiva registrada', [
                'uuid' => $liquidacion->uuid,
                'contratacion_id' => $contratacion->id,
                'neto_pagar' => $liquidacion->neto_pagar,
            ]);

            return $liquidacion->load(self::WITH);
        });
    }

    private function calcular(array $data): array
    {
        $fechaRetiro = Carbon::parse($data['fecha_retiro'])->startOfDay();
        $contratacion = Contratacion::with('tipoContrato')
            ->where('users_id', $data['user_id'])
            ->where('status', true)
            ->latest('inicio_contratacion')
            ->firstOrFail();
        $inicioContrato = Carbon::parse($contratacion->inicio_contratacion)->startOfDay();

        if ($fechaRetiro->lt($inicioContrato)) {
            throw new \LogicException('La fecha de retiro no puede ser anterior al inicio del contrato.');
        }

        if ($contratacion->fin_contrato && $fechaRetiro->gt(Carbon::parse($contratacion->fin_contrato)->endOfDay())) {
            throw new \LogicException('La fecha de retiro supera la fecha de finalización registrada en el contrato.');
        }

        if (LiquidacionRetiro::where('contratacion_id', $contratacion->id)->exists()) {
            throw new \LogicException('Este contrato ya tiene una liquidación definitiva.');
        }

        $ultimaNomina = Nomina::where('contratacion_id', $contratacion->id)
            ->where('liquidada', true)
            ->orderByDesc('periodo_fin')
            ->first();

        if ($ultimaNomina?->periodo_fin && $ultimaNomina->periodo_fin->gt($fechaRetiro)) {
            throw new \LogicException('Existe una nómina liquidada con fecha posterior al retiro.');
        }

        $inicioSalario = $ultimaNomina?->periodo_fin
            ? $ultimaNomina->periodo_fin->copy()->addDay()->startOfDay()
            : $inicioContrato->copy();
        $nominaPayload = null;
        $nominaPendiente = null;

        if ($inicioSalario->lte($fechaRetiro)) {
            $nominaPayload = [
                'user_id' => (int) $data['user_id'],
                'jornada_laboral_id' => (int) $data['jornada_laboral_id'],
                'periodo_inicio' => $inicioSalario->toDateString(),
                'periodo_fin' => $fechaRetiro->toDateString(),
            ];
            $nominaPendiente = $this->nominaService->preliquidar($nominaPayload);
        }

        $comisionesPendientes = Comision::where('user_id', $data['user_id'])
            ->where('status', 'aprobada')
            ->whereDate('periodo_fin', '<=', $fechaRetiro->toDateString())
            ->get();
        $totalComisionesPendientes = round((float) $comisionesPendientes->sum('valor'), 2);

        $inicioCesantias = $inicioContrato->copy()->max($fechaRetiro->copy()->startOfYear());
        $inicioPrima = $inicioContrato->copy()->max(
            $fechaRetiro->month <= 6
                ? $fechaRetiro->copy()->startOfYear()
                : $fechaRetiro->copy()->month(7)->startOfMonth()
        );
        $inicioPromedioVacaciones = $inicioContrato->copy()->max($fechaRetiro->copy()->subYear()->addDay());

        $diasContrato = $this->diasComerciales($inicioContrato, $fechaRetiro);
        $diasCesantias = $this->diasComerciales($inicioCesantias, $fechaRetiro);
        $diasPrima = $this->diasComerciales($inicioPrima, $fechaRetiro);

        $promedioVariableCesantias = $this->promedioVariableMensual($data['user_id'], $inicioCesantias, $fechaRetiro, true);
        $promedioVariablePrima = $this->promedioVariableMensual($data['user_id'], $inicioPrima, $fechaRetiro, true);
        $promedioComisionesVacaciones = $this->promedioVariableMensual($data['user_id'], $inicioPromedioVacaciones, $fechaRetiro, false);

        $baseSalarialCesantias = $this->ajusteSalarialService->salarioPromedioPeriodo($contratacion, $inicioCesantias, $fechaRetiro);
        $baseSalarialPrima = $this->ajusteSalarialService->salarioPromedioPeriodo($contratacion, $inicioPrima, $fechaRetiro);
        $baseSalarialVacaciones = $this->ajusteSalarialService->salarioPromedioPeriodo($contratacion, $inicioPromedioVacaciones, $fechaRetiro);

        $baseCesantias = round((float) $baseSalarialCesantias['salario_mensual'] + (float) $baseSalarialCesantias['auxilio_transporte'] + $promedioVariableCesantias, 2);
        $basePrima = round((float) $baseSalarialPrima['salario_mensual'] + (float) $baseSalarialPrima['auxilio_transporte'] + $promedioVariablePrima, 2);
        $baseVacaciones = round((float) $baseSalarialVacaciones['salario_mensual'] + $promedioComisionesVacaciones, 2);

        $cesantias = round($baseCesantias * $diasCesantias / 360, 2);
        $interesesCesantias = round($cesantias * $diasCesantias * 0.12 / 360, 2);
        $primaServicios = round($basePrima * $diasPrima / 360, 2);

        $diasVacacionesGanados = $diasContrato * 15 / 360;
        $diasVacacionesUsados = (float) Vacacion::where('user_id', $data['user_id'])
            ->where('status', 'aprobada')
            ->whereDate('fecha_inicio', '>=', $inicioContrato->toDateString())
            ->whereDate('fecha_inicio', '<=', $fechaRetiro->toDateString())
            ->sum('dias_habiles');
        $diasVacacionesPendientes = round(max(0, $diasVacacionesGanados - $diasVacacionesUsados), 4);
        $vacaciones = round(($baseVacaciones / 30) * $diasVacacionesPendientes, 2);

        $pagoNoPrestacional = round((float) ($nominaPendiente['pago_no_prestacional'] ?? 0), 2);
        $comisionesEnNomina = round((float) ($nominaPendiente['total_comisiones'] ?? 0), 2);
        $salarioPendiente = round(
            (float) ($nominaPendiente['total_devengado'] ?? 0)
            - $pagoNoPrestacional
            - $comisionesEnNomina,
            2
        );
        $indemnizacion = round((float) ($data['indemnizacion'] ?? 0), 2);
        $configuracion = ConfiguracionNomina::where('status', true)->latest()->first();
        $comisionesFueraNomina = max(0, $totalComisionesPendientes - $comisionesEnNomina);
        $deduccionesComisiones = round($comisionesFueraNomina * (
            ((float) ($configuracion?->porcentaje_salud_empleado ?? 4)
                + (float) ($configuracion?->porcentaje_pension_empleado ?? 4)) / 100
        ), 2);
        $deduccionesNomina = round((float) ($nominaPendiente['total_deducciones'] ?? 0) + $deduccionesComisiones, 2);
        $deduccionesFinales = round((float) ($data['deducciones'] ?? 0), 2);
        $totalDevengado = round(
            $salarioPendiente
            + $pagoNoPrestacional
            + $totalComisionesPendientes
            + $cesantias
            + $interesesCesantias
            + $primaServicios
            + $vacaciones
            + $indemnizacion,
            2
        );
        $totalDeducciones = round($deduccionesNomina + $deduccionesFinales, 2);
        $advertencias = $nominaPendiente['advertencias'] ?? [];

        if ($data['motivo_retiro'] === 'terminacion_sin_justa_causa' && $indemnizacion <= 0) {
            $advertencias[] = 'La terminación sin justa causa no tiene indemnización registrada; debe validarse manualmente.';
        }

        return [
            'user_id' => (int) $data['user_id'],
            'contratacion_id' => $contratacion->id,
            'jornada_laboral_id' => (int) $data['jornada_laboral_id'],
            'fecha_retiro' => $fechaRetiro->toDateString(),
            'motivo_retiro' => $data['motivo_retiro'],
            'periodo_salario_inicio' => $nominaPayload['periodo_inicio'] ?? null,
            'periodo_salario_fin' => $nominaPayload['periodo_fin'] ?? null,
            'dias_contrato' => $diasContrato,
            'dias_cesantias' => $diasCesantias,
            'dias_prima' => $diasPrima,
            'dias_vacaciones' => $diasVacacionesPendientes,
            'dias_vacaciones_pendientes' => $diasVacacionesPendientes,
            'base_cesantias' => $baseCesantias,
            'base_prima' => $basePrima,
            'base_vacaciones' => $baseVacaciones,
            'salario_pendiente' => $salarioPendiente,
            'pago_no_prestacional' => $pagoNoPrestacional,
            'comisiones_pendientes' => $totalComisionesPendientes,
            'cesantias' => $cesantias,
            'intereses_cesantias' => $interesesCesantias,
            'prima_servicios' => $primaServicios,
            'vacaciones' => $vacaciones,
            'indemnizacion' => $indemnizacion,
            'total_devengado' => $totalDevengado,
            'deducciones_nomina' => $deduccionesNomina,
            'deducciones_comisiones_pendientes' => $deduccionesComisiones,
            'deducciones_finales' => $deduccionesFinales,
            'total_deducciones' => $totalDeducciones,
            'neto_pagar' => round($totalDevengado - $totalDeducciones, 2),
            'detalle_comisiones' => $comisionesPendientes->map(fn ($comision) => [
                'uuid' => $comision->uuid,
                'concepto' => $comision->concepto,
                'valor' => $comision->valor,
            ])->values(),
            'advertencias' => array_values(array_unique($advertencias)),
            '_nomina_payload' => $nominaPayload,
            '_comisiones_pendientes_ids' => $comisionesPendientes->pluck('id')->all(),
        ];
    }

    private function promedioVariableMensual(int $userId, Carbon $inicio, Carbon $fin, bool $incluirHorasExtra): float
    {
        $nominas = Nomina::where('user_id', $userId)
            ->where('liquidada', true)
            ->whereDate('periodo_fin', '>=', $inicio->toDateString())
            ->whereDate('periodo_inicio', '<=', $fin->toDateString())
            ->get();
        $total = (float) $nominas->sum('total_comisiones');

        if ($incluirHorasExtra) {
            $total += (float) $nominas->sum(fn ($nomina) => (float) $nomina->valor_horas_extras_diurnas
                + (float) $nomina->valor_horas_extras_nocturnas
                + (float) $nomina->valor_horas_festivas
                + (float) $nomina->valor_horas_nocturnas_festivas);
        }

        $total += (float) Comision::where('user_id', $userId)
            ->where('status', 'aprobada')
            ->whereDate('periodo_fin', '>=', $inicio->toDateString())
            ->whereDate('periodo_inicio', '<=', $fin->toDateString())
            ->sum('valor');

        return round(($total / max(1, $this->diasComerciales($inicio, $fin))) * 30, 2);
    }

    private function diasComerciales(Carbon $inicio, Carbon $fin): int
    {
        $inicio = $inicio->copy()->startOfDay();
        $fin = $fin->copy()->startOfDay();

        if ($inicio->gt($fin)) {
            return 0;
        }

        if ($inicio->isSameMonth($fin)) {
            return min(30, $this->diasComercialesMes($inicio, $fin));
        }

        $dias = $this->diasComercialesMes($inicio, $inicio->copy()->endOfMonth()->startOfDay());
        $cursor = $inicio->copy()->addMonthNoOverflow()->startOfMonth();

        while ($cursor->lt($fin->copy()->startOfMonth())) {
            $dias += 30;
            $cursor->addMonthNoOverflow();
        }

        return max(1, $dias + $this->diasComercialesMes($fin->copy()->startOfMonth(), $fin));
    }

    private function diasComercialesMes(Carbon $inicio, Carbon $fin): int
    {
        $ultimoDiaMes = $fin->copy()->endOfMonth()->day;
        $diaInicio = min($inicio->day, 30);
        $diaFin = $fin->day === $ultimoDiaMes ? 30 : min($fin->day, 30);

        return max(1, $diaFin - $diaInicio + 1);
    }

    private function respuestaPublica(array $calculo): array
    {
        unset($calculo['_nomina_payload'], $calculo['_comisiones_pendientes_ids']);

        return $calculo;
    }

    private function datosPersistencia(array $calculo): array
    {
        return collect($this->respuestaPublica($calculo))
            ->except(['dias_vacaciones', 'detalle_comisiones', 'advertencias'])
            ->all();
    }
}
