<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Contratacion;
use App\Models\Nomina\Comision;
use App\Models\Nomina\LiquidacionPrestacion;
use App\Models\Nomina\Nomina;
use App\Models\Nomina\Vacacion;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiquidacionPrestacionService
{
    private const WITH = ['empleado:id,name,email', 'contratacion.empresa', 'vacacion'];

    public function __construct(
        private readonly AjusteSalarialContratacionService $ajusteSalarialService,
        private readonly VacacionSaldoService $vacacionSaldoService,
    ) {}

    /**
     * Tipos soportados con sus etiquetas.
     */
    public static function tipos(): array
    {
        return [
            'prima'                  => 'Prima de servicios',
            'cesantias'              => 'Cesantías',
            'vacaciones_ordinarias'  => 'Vacaciones ordinarias',
            'vacaciones_compensadas' => 'Vacaciones compensadas',
        ];
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return LiquidacionPrestacion::with(self::WITH)
            ->when(! empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(! empty($filters['tipo']), fn ($q) => $q->where('tipo', $filters['tipo']))
            ->when(! empty($filters['anio']), fn ($q) => $q->whereYear('periodo_inicio', $filters['anio']))
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];
                $q->whereHas('empleado', fn ($e) => $e
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->orderByDesc('fecha_liquidacion')
            ->paginate(min(max((int) ($filters['per_page'] ?? 15), 1), 100));
    }

    public function preliquidar(array $data): array
    {
        return $this->calcular($data);
    }

    public function vacacionesAprobadasPendientes(int $userId, ?string $tipoLiquidacion = null)
    {
        $contrato = $this->vacacionSaldoService->contratoActivo($userId);

        if (! $contrato) {
            return collect();
        }

        $inicioContrato = Carbon::parse($contrato->inicio_contratacion)->toDateString();

        $tipoVacacion = match ($tipoLiquidacion) {
            'vacaciones_ordinarias' => 'ordinarias',
            'vacaciones_compensadas' => 'compensadas',
            default => null,
        };

        return Vacacion::query()
            ->select(['id', 'uuid', 'user_id', 'fecha_inicio', 'fecha_fin', 'dias_habiles', 'tipo', 'motivo'])
            ->where('user_id', $userId)
            ->where(function ($query) use ($contrato, $inicioContrato) {
                $query->where('contratacion_id', $contrato->id)
                    ->orWhere(function ($legacy) use ($inicioContrato) {
                        $legacy->whereNull('contratacion_id')
                            ->whereDate('fecha_inicio', '>=', $inicioContrato);
                    });
            })
            ->where('status', 'aprobada')
            ->when($tipoVacacion, fn ($query) => $query->where('tipo', $tipoVacacion))
            ->whereDoesntHave('liquidacionPrestacion')
            ->orderBy('fecha_inicio')
            ->get();
    }

    public function liquidar(array $data): LiquidacionPrestacion
    {
        return DB::transaction(function () use ($data) {
            $calculo = $this->calcular($data);

            if (! empty($calculo['vacacion_id'])) {
                $vacacion = Vacacion::whereKey($calculo['vacacion_id'])->lockForUpdate()->firstOrFail();

                if ($vacacion->liquidacionPrestacion()->exists()) {
                    throw new \LogicException('Esta solicitud de vacaciones ya fue liquidada.');
                }
            }

            // Validar duplicado o cruce del mismo tipo y período
            $duplicado = LiquidacionPrestacion::where('user_id', $calculo['user_id'])
                ->where('tipo', $calculo['tipo'])
                ->whereDate('periodo_inicio', '<=', $calculo['periodo_fin'])
                ->whereDate('periodo_fin', '>=', $calculo['periodo_inicio'])
                ->exists();

            if ($duplicado) {
                throw new \LogicException('Ya existe una liquidación del mismo tipo que se cruza con ese período.');
            }

            $liquidacion = LiquidacionPrestacion::create([
                'user_id'            => $calculo['user_id'],
                'contratacion_id'    => $calculo['contratacion_id'],
                'vacacion_id'        => $calculo['vacacion_id'] ?? null,
                'tipo'               => $calculo['tipo'],
                'periodo_inicio'     => $calculo['periodo_inicio'],
                'periodo_fin'        => $calculo['periodo_fin'],
                'dias_liquidados'    => $calculo['dias_liquidados'],
                'salario_mensual'    => $calculo['salario_mensual'],
                'auxilio_transporte' => $calculo['auxilio_transporte'],
                'promedio_variable'  => $calculo['promedio_variable'],
                'base_calculo'       => $calculo['base_calculo'],
                'valor_calculado'    => $calculo['valor_calculado'],
                'intereses_cesantias' => $calculo['intereses_cesantias'],
                'dias_vacaciones'    => $calculo['dias_vacaciones'],
                'total_liquidado'    => $calculo['total_liquidado'],
                'detalle_calculo'    => $calculo,
                'fecha_liquidacion'  => now(),
            ]);

            Log::info('Prestación liquidada', [
                'uuid'  => $liquidacion->uuid,
                'tipo'  => $liquidacion->tipo,
                'user'  => $liquidacion->user_id,
                'total' => $liquidacion->total_liquidado,
            ]);

            return $liquidacion->load(self::WITH);
        });
    }

    // ─── Cálculo central ─────────────────────────────────────────────────────

    private function calcular(array $data): array
    {
        $tipo     = $data['tipo'];
        $userId   = (int) $data['user_id'];
        $vacacion = null;

        if (in_array($tipo, ['vacaciones_ordinarias', 'vacaciones_compensadas'], true)) {
            $tipoVacacion = $tipo === 'vacaciones_ordinarias' ? 'ordinarias' : 'compensadas';
            $vacacion = Vacacion::where('uuid', $data['vacacion_uuid'])
                ->where('user_id', $userId)
                ->where('status', 'aprobada')
                ->where('tipo', $tipoVacacion)
                ->first();

            if (! $vacacion) {
                throw new \LogicException(
                    'La solicitud seleccionada no corresponde al tipo de vacaciones aprobado para el empleado.'
                );
            }

            if ($vacacion->liquidacionPrestacion()->exists()) {
                throw new \LogicException('Esta solicitud de vacaciones ya fue liquidada.');
            }

            $inicio = Carbon::parse($vacacion->fecha_inicio)->startOfDay();
            $fin    = Carbon::parse($vacacion->fecha_fin)->endOfDay();
        } else {
            $inicio = Carbon::parse($data['periodo_inicio'])->startOfDay();
            $fin    = Carbon::parse($data['periodo_fin'])->endOfDay();
        }

        $this->validarTipo($tipo);

        $contratacion = Contratacion::where('users_id', $userId)
            ->where('status', 1)
            ->latest('inicio_contratacion')
            ->firstOrFail();

        if ($vacacion && $vacacion->contratacion_id && $vacacion->contratacion_id !== $contratacion->id) {
            throw new \LogicException('La solicitud de vacaciones no pertenece al contrato activo del empleado.');
        }

        $inicioContrato = Carbon::parse($contratacion->inicio_contratacion)->startOfDay();
        $inicioEfectivo = $inicio->copy()->max($inicioContrato);
        $finEfectivo    = $fin->copy();

        if ($contratacion->fin_contrato) {
            $finEfectivo = $finEfectivo->min(Carbon::parse($contratacion->fin_contrato)->endOfDay());
        }

        if ($inicioEfectivo->gt($finEfectivo)) {
            throw new \LogicException('El contrato activo no cubre el período seleccionado.');
        }

        $diasLiquidados = $this->diasComerciales($inicioEfectivo, $finEfectivo->copy()->startOfDay());
        $esVacaciones = in_array($tipo, ['vacaciones_ordinarias', 'vacaciones_compensadas'], true);
        $baseSalarial = $esVacaciones
            ? $this->ajusteSalarialService->salarioVigente($contratacion, $inicioEfectivo)
            : $this->ajusteSalarialService->salarioPromedioPeriodo(
                $contratacion,
                $inicioEfectivo,
                $finEfectivo->copy()->startOfDay()
            );
        $salarioMensual = (float) $baseSalarial['salario_mensual'];
        $auxilioMensual = (float) $baseSalarial['auxilio_transporte'];

        if ($esVacaciones) {
            $finPromedioVariable = $inicioEfectivo->copy()->subDay();
            $inicioPromedioVariable = $finPromedioVariable->copy()->subYear()->addDay()->max($inicioContrato);
            $promedioVariable = $inicioPromedioVariable->lte($finPromedioVariable)
                ? $this->promedioVariableMensual($userId, $inicioPromedioVariable, $finPromedioVariable)
                : 0;
        } else {
            $promedioVariable = $this->promedioVariableMensual(
                $userId,
                $inicioEfectivo,
                $finEfectivo->copy()->startOfDay()
            );
        }

        return match ($tipo) {
            'prima'                  => $this->calcularPrima($userId, $contratacion->id, $diasLiquidados, $salarioMensual, $auxilioMensual, $promedioVariable, $inicioEfectivo, $finEfectivo->copy()->startOfDay()),
            'cesantias'              => $this->calcularCesantias($userId, $contratacion->id, $diasLiquidados, $salarioMensual, $auxilioMensual, $promedioVariable, $inicioEfectivo, $finEfectivo->copy()->startOfDay()),
            'vacaciones_ordinarias',
            'vacaciones_compensadas' => $this->calcularVacaciones($tipo, $vacacion, $contratacion->id, (int) $vacacion->dias_habiles, $salarioMensual, $promedioVariable, $inicioEfectivo, $finEfectivo->copy()->startOfDay()),
        };
    }

    private function calcularPrima(
        int $userId,
        int $contratacionId,
        int $dias,
        float $salarioMensual,
        float $auxilioMensual,
        float $promedioVariable,
        Carbon $inicio,
        Carbon $fin,
    ): array {
        // Base prima: salario + auxilio de transporte + promedio variable (Art. 306 CST)
        $base   = round($salarioMensual + $auxilioMensual + $promedioVariable, 2);
        $valor  = round($base * $dias / 360, 2);

        return $this->respuesta('prima', $userId, $contratacionId, $dias, $salarioMensual, $auxilioMensual, $promedioVariable, $base, $valor, 0, null, $valor, $inicio, $fin);
    }

    private function calcularCesantias(
        int $userId,
        int $contratacionId,
        int $dias,
        float $salarioMensual,
        float $auxilioMensual,
        float $promedioVariable,
        Carbon $inicio,
        Carbon $fin,
    ): array {
        // Base cesantías: salario + auxilio + promedio variable (Art. 249 CST)
        $base              = round($salarioMensual + $auxilioMensual + $promedioVariable, 2);
        $cesantias         = round($base * $dias / 360, 2);
        // Intereses sobre cesantías: 12% anual (Ley 52/1975)
        $intereses         = round($cesantias * $dias * 0.12 / 360, 2);
        $total             = round($cesantias + $intereses, 2);

        return $this->respuesta('cesantias', $userId, $contratacionId, $dias, $salarioMensual, $auxilioMensual, $promedioVariable, $base, $cesantias, $intereses, null, $total, $inicio, $fin);
    }

    private function calcularVacaciones(
        string $tipoLiquidacion,
        Vacacion $vacacion,
        int $contratacionId,
        int $diasPeriodo,
        float $salarioMensual,
        float $promedioVariable,
        Carbon $inicio,
        Carbon $fin,
    ): array {
        $userId = (int) $vacacion->user_id;

        // Vacaciones: solo salario base + promedio comisiones (sin auxilio) — Art. 192 CST
        $base = round($salarioMensual + $promedioVariable, 2);

        $diasSolicitud = (float) $vacacion->dias_habiles;
        $saldo = $this->vacacionSaldoService->resumen(
            $userId,
            now(),
            $vacacion->id
        );
        $diasPendientes = (float) $saldo['dias_disponibles'];

        if ($diasSolicitud > $diasPendientes) {
            throw new \LogicException(
                "La solicitud aprobada tiene {$diasSolicitud} días, pero solo quedan {$diasPendientes} días disponibles."
            );
        }

        $valor = round(($base / 30) * $diasSolicitud, 2);

        return $this->respuesta($tipoLiquidacion, $userId, $contratacionId, $diasPeriodo, $salarioMensual, 0, $promedioVariable, $base, $valor, 0, $diasSolicitud, $valor, $inicio, $fin, [
            'vacacion_id'           => $vacacion->id,
            'vacacion_uuid'         => $vacacion->uuid,
            'vacacion_motivo'       => $vacacion->motivo,
            'dias_ganados_total'   => $saldo['dias_ganados'],
            'dias_usados'          => $saldo['dias_usados'],
            'dias_comprometidos'   => $saldo['dias_comprometidos'],
            'dias_disponibles'     => $diasPendientes,
            'dias_solicitud'       => $diasSolicitud,
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function respuesta(
        string $tipo,
        int $userId,
        int $contratacionId,
        int $diasLiquidados,
        float $salarioMensual,
        float $auxilioTransporte,
        float $promedioVariable,
        float $base,
        float $valorCalculado,
        float $interesesCesantias,
        ?float $diasVacaciones,
        float $totalLiquidado,
        Carbon $inicio,
        Carbon $fin,
        array $extra = [],
    ): array {
        return array_merge([
            'tipo'                 => $tipo,
            'user_id'              => $userId,
            'contratacion_id'      => $contratacionId,
            'periodo_inicio'       => $inicio->toDateString(),
            'periodo_fin'          => $fin->toDateString(),
            'dias_liquidados'      => $diasLiquidados,
            'salario_mensual'      => $salarioMensual,
            'auxilio_transporte'   => $auxilioTransporte,
            'promedio_variable'    => $promedioVariable,
            'base_calculo'         => $base,
            'valor_calculado'      => $valorCalculado,
            'intereses_cesantias'  => $interesesCesantias,
            'dias_vacaciones'      => $diasVacaciones,
            'total_liquidado'      => $totalLiquidado,
        ], $extra);
    }

    /**
     * Promedio mensual de variables salariales del período.
     * Incluye comisiones ya aplicadas en nómina y comisiones aprobadas pendientes.
     */
    private function promedioVariableMensual(int $userId, Carbon $inicio, Carbon $fin): float
    {
        $nominas = Nomina::where('user_id', $userId)
            ->operativas()
            ->where('liquidada', true)
            ->whereDate('periodo_fin', '>=', $inicio->toDateString())
            ->whereDate('periodo_inicio', '<=', $fin->toDateString())
            ->get();

        $total = (float) $nominas->sum('total_comisiones');
        $total += (float) Comision::where('user_id', $userId)
            ->where('status', 'aprobada')
            ->whereDate('periodo_fin', '>=', $inicio->toDateString())
            ->whereDate('periodo_inicio', '<=', $fin->toDateString())
            ->sum('valor');
        $total += (float) $nominas->sum(fn ($n) =>
            (float) $n->valor_horas_extras_diurnas
            + (float) $n->valor_horas_extras_nocturnas
            + (float) $n->valor_horas_festivas
            + (float) $n->valor_horas_nocturnas_festivas
        );

        $dias = $this->diasComerciales($inicio, $fin);

        return round(($total / max(1, $dias)) * 30, 2);
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

    private function validarTipo(string $tipo): void
    {
        if (! array_key_exists($tipo, self::tipos())) {
            throw new \InvalidArgumentException("Tipo de prestación inválido: {$tipo}");
        }
    }
}
