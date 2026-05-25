<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Contratacion;
use App\Models\Nomina\Descuento;
use App\Models\Nomina\HoraExtra;
use App\Models\Nomina\Incapacidad;
use App\Models\Nomina\JornadaLaboral;
use App\Models\Nomina\Permiso;
use App\Models\Nomina\Nomina;
use App\Models\Nomina\Vacacion;
use App\Models\Nomina\Valor;
use App\Models\Nomina\WorkSession;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NominaService
{
    private const WITH = [
        'empleado.sede',
        'contratacion.empresa',
        'descuento',
        'jornadaLaboral',
        'transacionalRegistro',
    ];

    // Porcentajes de ley colombiana sobre la hora normal
    private const RECARGO_EXTRA_DIURNA     = 0.25; // +25%
    private const RECARGO_EXTRA_NOCTURNA   = 0.75; // +75%
    private const RECARGO_FESTIVA          = 0.75; // +75%
    private const RECARGO_NOCTURNA_FESTIVA = 1.10; // +110%
    private const PORCENTAJE_INCAPACIDAD   = 0.6667;

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Nomina::with(self::WITH)
            ->when(!empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['jornada_laboral_id']), fn($q) =>
                $q->where('jornada_laboral_id', $filters['jornada_laboral_id']))
            ->when(!empty($filters['periodo_inicio']), fn($q) =>
                $q->whereDate('periodo_inicio', '>=', $filters['periodo_inicio']))
            ->when(!empty($filters['periodo_fin']), fn($q) =>
                $q->whereDate('periodo_fin', '<=', $filters['periodo_fin']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where(function ($query) use ($search) {
                    $query->whereHas('empleado', fn($empleado) =>
                        $empleado->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('contratacion', fn($contrato) =>
                            $contrato->where('cargo', 'like', "%{$search}%")
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

    public function store(array $data): Nomina
    {
        return DB::transaction(function () use ($data) {
            $nomina = Nomina::create($data);

            Log::info('Nómina creada', [
                'uuid'    => $nomina->uuid,
                'user_id' => $nomina->user_id,
            ]);

            return $nomina->load(self::WITH);
        });
    }

    public function update(string $uuid, array $data): Nomina
    {
        return DB::transaction(function () use ($uuid, $data) {
            $nomina = $this->getByUuid($uuid);

            $nomina->update($data);

            Log::info('Nómina actualizada', [
                'uuid'    => $nomina->uuid,
                'user_id' => $nomina->user_id,
            ]);

            return $nomina->fresh(self::WITH);
        });
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $nomina = $this->getByUuid($uuid);

            $nomina->delete();

            Log::info('Nómina eliminada', ['uuid' => $nomina->uuid]);
        });
    }

    /**
     * Calcula y persiste la nómina de un empleado para el período indicado.
     * Las horas se obtienen automáticamente de WorkSessions.
     */
    public function liquidar(array $data): Nomina
    {
        return DB::transaction(function () use ($data) {
            $this->validarPeriodoSinLiquidar($data['user_id'], $data['periodo_inicio'], $data['periodo_fin']);
            $calculo = $this->calcular($data);

            // ── Persistencia ──────────────────────────────────────────────
            $nomina = Nomina::create([
                'user_id'            => $calculo['user_id'],
                'jornada_laboral_id' => $data['jornada_laboral_id'],
                'contratacion_id'    => $calculo['contratacion_id'],
                'descuento_id'       => $calculo['descuento_id'],

                'periodo_inicio' => $calculo['periodo_inicio'],
                'periodo_fin'    => $calculo['periodo_fin'],

                'horas_normales'          => $calculo['horas_normales'],
                'horas_extras_nocturnas'  => $calculo['horas_extras_nocturnas'],
                'horas_extras_diurnas'    => $calculo['horas_extras_diurnas'],
                'horas_festivas'          => $calculo['horas_festivas'],
                'horas_nocturnas_festivas'=> $calculo['horas_nocturnas_festivas'],

                'valor_hora_normal'          => $calculo['valor_hora_normal'],
                'valor_hora_nocturna'        => $calculo['valor_hora_nocturna'],
                'valor_hora_dominical'       => $calculo['valor_hora_dominical'],
                'valor_hora_dominical_extra' => $calculo['valor_hora_dominical_extra'],

                'salario_base_devengado'         => $calculo['salario_base_devengado'],
                'auxilio_transporte'             => $calculo['auxilio_transporte'],
                'valor_horas_normales'           => $calculo['valor_horas_normales'],
                'valor_horas_extras_nocturnas'   => $calculo['valor_horas_extras_nocturnas'],
                'valor_horas_extras_diurnas'     => $calculo['valor_horas_extras_diurnas'],
                'valor_horas_festivas'           => $calculo['valor_horas_festivas'],
                'valor_horas_nocturnas_festivas' => $calculo['valor_horas_nocturnas_festivas'],
                'total_devengado'                => $calculo['total_devengado'],

                'deduccion_salud'              => $calculo['deduccion_salud'],
                'deduccion_pension'            => $calculo['deduccion_pension'],
                'total_descuentos_adicionales' => $calculo['total_descuentos_adicionales'],
                'total_deducciones'            => $calculo['total_deducciones'],

                'salario_neto'       => $calculo['salario_neto'],
                'liquidada'          => true,
                'fecha_liquidacion'  => now(),
            ]);

            Log::info('Nómina liquidada', [
                'uuid'            => $nomina->uuid,
                'user_id'         => $calculo['user_id'],
                'periodo'         => $calculo['periodo_inicio'] . ' → ' . $calculo['periodo_fin'],
                'total_devengado' => $calculo['total_devengado'],
                'total_deducciones' => $calculo['total_deducciones'],
                'salario_neto'    => $calculo['salario_neto'],
            ]);

            return $nomina->load(self::WITH);
        });
    }

    public function preliquidar(array $data): array
    {
        return $this->calcular($data);
    }

    private function calcular(array $data): array
    {
        $userId = $data['user_id'];
        $inicio = Carbon::parse($data['periodo_inicio'])->startOfDay();
        $fin    = Carbon::parse($data['periodo_fin'])->endOfDay();

        $contratacion = Contratacion::where('users_id', $userId)
            ->where('status', 1)
            ->latest('inicio_contratacion')
            ->firstOrFail();

        $valor = Valor::where('status', true)->latest()->firstOrFail();
        $jornada = JornadaLaboral::findOrFail($data['jornada_laboral_id']);

        $inicioLiquidable = $inicio->copy()->max(Carbon::parse($contratacion->inicio_contratacion)->startOfDay());
        $finLiquidable = $fin->copy();
        if ($contratacion->fin_contrato) {
            $finLiquidable = $finLiquidable->min(Carbon::parse($contratacion->fin_contrato)->endOfDay());
        }

        if ($inicioLiquidable->gt($finLiquidable)) {
            throw new \LogicException('El contrato activo no cubre el período seleccionado.');
        }

        $sessions = WorkSession::where('user_id', $userId)
            ->whereBetween('registro_diario', [$inicioLiquidable->toDateString(), $finLiquidable->toDateString()])
            ->get();

        $totalMinutos = (int) $sessions->sum('minutos_trabajados');
        $festivoMinutos = (float) $sessions->sum('festivo_minutos');
        $sabadoMinutos = (float) $sessions->sum('sabado_minutos');
        $ordinariosMinutos = max(0, $totalMinutos - $festivoMinutos - $sabadoMinutos);

        $diasHabiles = $this->contarDiasHabiles($inicioLiquidable, $finLiquidable);
        $minutosEsperados = ($jornada->horas_semanales / 5) * $diasHabiles * 60;
        $horasNormales = round(min($ordinariosMinutos, $minutosEsperados) / 60, 2);
        $horasFestivasTotal = round(($festivoMinutos + $sabadoMinutos) / 60, 2);

        $extrasAprobadas = HoraExtra::where('user_id', $userId)
            ->whereBetween('fecha', [$inicioLiquidable->toDateString(), $finLiquidable->toDateString()])
            ->where('status', 'aprobada')
            ->get();

        $horasExtrasDiurnas = round((float) $extrasAprobadas->where('tipo', 'diurna')->sum('horas'), 2);
        $horasExtrasNocturnas = round((float) $extrasAprobadas->where('tipo', 'nocturna')->sum('horas'), 2);
        $horasNocturnasFestivas = round((float) $extrasAprobadas->where('tipo', 'nocturna_festiva')->sum('horas'), 2);
        $horasFestivasTotal = round($horasFestivasTotal + (float) $extrasAprobadas->where('tipo', 'festiva')->sum('horas'), 2);

        $diasLiquidables = min(30, $inicioLiquidable->diffInDays($finLiquidable) + 1);
        $salarioMensual = (float) $contratacion->base_salario;
        $valorDia = round($salarioMensual / 30, 6);
        $valorHoraBase = (float) ($valor->valor_hora_normal ?: round($salarioMensual / 240, 2));

        $diasIncapacidad = $this->contarDiasNovedad(
            Incapacidad::where('user_id', $userId)
                ->where('status', true)
                ->whereDate('inicio', '<=', $finLiquidable->toDateString())
                ->whereDate('fin', '>=', $inicioLiquidable->toDateString())
                ->get(),
            $inicioLiquidable,
            $finLiquidable,
            'inicio',
            'fin'
        );

        $vacacionesAprobadas = Vacacion::where('user_id', $userId)
            ->where('status', 'aprobada')
            ->whereDate('fecha_inicio', '<=', $finLiquidable->toDateString())
            ->whereDate('fecha_fin', '>=', $inicioLiquidable->toDateString())
            ->get();

        $diasVacacionesCompensadas = (int) $vacacionesAprobadas
            ->where('tipo', 'compensadas')
            ->sum('dias_habiles');

        $permisosNoRemunerados = Permiso::where('user_id', $userId)
            ->whereBetween('fecha', [$inicioLiquidable->toDateString(), $finLiquidable->toDateString()])
            ->where('status', 'aprobado')
            ->where('es_remunerado', false)
            ->get();

        $minutosNoRemunerados = $permisosNoRemunerados->sum(fn($p) =>
            Carbon::parse($p->hora_inicio)->diffInMinutes(Carbon::parse($p->hora_fin))
        );

        $valorPermisosNoRemunerados = round(($minutosNoRemunerados / 60) * $valorHoraBase, 2);
        $salarioBasePeriodo = round(($valorDia * max(0, $diasLiquidables - $diasIncapacidad))
            + ($valorDia * $diasIncapacidad * self::PORCENTAJE_INCAPACIDAD)
            + ($valorDia * $diasVacacionesCompensadas), 2);
        $auxilioTransportePeriodo = round((float) $contratacion->auxilio_transporte * ($diasLiquidables / 30), 2);

        // El salario mensual ya remunera las horas ordinarias; se guardan para control, no se suman otra vez.
        $valorHorasNormales = 0;
        $valorHorasExtrasDiurnas = round($horasExtrasDiurnas * $valorHoraBase * (1 + self::RECARGO_EXTRA_DIURNA), 2);
        $valorHorasExtrasNocturnas = round($horasExtrasNocturnas * $valorHoraBase * (1 + self::RECARGO_EXTRA_NOCTURNA), 2);
        $valorHorasFestivas = round($horasFestivasTotal * $valorHoraBase * (1 + self::RECARGO_FESTIVA), 2);
        $valorHorasNocturnasFestivas = round($horasNocturnasFestivas * $valorHoraBase * (1 + self::RECARGO_NOCTURNA_FESTIVA), 2);

        $totalDevengado = $salarioBasePeriodo
            + $auxilioTransportePeriodo
            + $valorHorasNormales
            + $valorHorasExtrasDiurnas
            + $valorHorasExtrasNocturnas
            + $valorHorasFestivas
            + $valorHorasNocturnasFestivas;

        $baseParaDeducciones = $salarioBasePeriodo
            + $valorHorasNormales
            + $valorHorasExtrasDiurnas
            + $valorHorasExtrasNocturnas
            + $valorHorasFestivas
            + $valorHorasNocturnasFestivas;

        $deduccionSalud = round($baseParaDeducciones * 0.04, 2);
        $deduccionPension = round($baseParaDeducciones * 0.04, 2);
        $descuentosNomina = $this->calcularDescuentos($userId, $inicioLiquidable, $finLiquidable, $data['descuento_id'] ?? null);
        $totalDescuentosAdicionales = round($descuentosNomina['valor'] + $valorPermisosNoRemunerados, 2);
        $totalDeducciones = round($deduccionSalud + $deduccionPension + $totalDescuentosAdicionales, 2);
        $salarioNeto = round($totalDevengado - $totalDeducciones, 2);

        return [
            'user_id' => $userId,
            'contratacion_id' => $contratacion->id,
            'descuento_id' => $descuentosNomina['descuento_id'],
            'periodo_inicio' => $inicio->toDateString(),
            'periodo_fin' => $fin->toDateString(),
            'dias_liquidados' => $diasLiquidables,
            'dias_incapacidad' => $diasIncapacidad,
            'dias_vacaciones_compensadas' => $diasVacacionesCompensadas,
            'minutos_permisos_no_remunerados' => $minutosNoRemunerados,
            'horas_normales' => $horasNormales,
            'horas_extras_nocturnas' => $horasExtrasNocturnas,
            'horas_extras_diurnas' => $horasExtrasDiurnas,
            'horas_festivas' => $horasFestivasTotal,
            'horas_nocturnas_festivas' => $horasNocturnasFestivas,
            'valor_hora_normal' => $valorHoraBase,
            'valor_hora_nocturna' => $valor->valor_hora_nocturna,
            'valor_hora_dominical' => $valor->valor_hora_dominical,
            'valor_hora_dominical_extra' => $valor->valor_hora_dominical_extra,
            'salario_base_devengado' => $salarioBasePeriodo,
            'auxilio_transporte' => $auxilioTransportePeriodo,
            'valor_horas_normales' => $valorHorasNormales,
            'valor_horas_extras_nocturnas' => $valorHorasExtrasNocturnas,
            'valor_horas_extras_diurnas' => $valorHorasExtrasDiurnas,
            'valor_horas_festivas' => $valorHorasFestivas,
            'valor_horas_nocturnas_festivas' => $valorHorasNocturnasFestivas,
            'total_devengado' => round($totalDevengado, 2),
            'deduccion_salud' => $deduccionSalud,
            'deduccion_pension' => $deduccionPension,
            'total_descuentos_adicionales' => $totalDescuentosAdicionales,
            'total_deducciones' => $totalDeducciones,
            'salario_neto' => $salarioNeto,
            'detalle_descuentos' => $descuentosNomina['detalle'],
        ];
    }

    private function validarPeriodoSinLiquidar(int $userId, string $periodoInicio, string $periodoFin): void
    {
        $existe = Nomina::where('user_id', $userId)
            ->whereDate('periodo_inicio', $periodoInicio)
            ->whereDate('periodo_fin', $periodoFin)
            ->where('liquidada', true)
            ->exists();

        if ($existe) {
            throw new \LogicException('Este empleado ya tiene una nómina liquidada para el período seleccionado.');
        }
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
            $desde = Carbon::parse($item->{$campoInicio})->startOfDay()->max($inicio);
            $hasta = Carbon::parse($item->{$campoFin})->endOfDay()->min($fin);

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

        $diasPeriodo = $inicio->diffInDays($fin) + 1;

        return $diasPeriodo > 15 ? 2 : 1;
    }

    private function contarDiasHabiles(Carbon $inicio, Carbon $fin): int
    {
        $dias    = 0;
        $current = $inicio->copy()->startOfDay();

        while ($current->lte($fin)) {
            if (!$current->isWeekend()) {
                $dias++;
            }
            $current->addDay();
        }

        return $dias;
    }
}
