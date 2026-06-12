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
        'novedadesRetroactivas',
        'transacionalRegistro',
    ];

    // Porcentajes de ley colombiana sobre la hora normal
    private const RECARGO_EXTRA_DIURNA = 0.25; // +25%

    private const RECARGO_EXTRA_NOCTURNA = 0.75; // +75%

    private const RECARGO_FESTIVA = 0.75; // +75%

    private const RECARGO_NOCTURNA_FESTIVA = 1.10; // +110%

    private const PORCENTAJE_INCAPACIDAD = 0.6667;

    private const HORA_INICIO_NOCTURNA = '19:00:00';

    private const HORA_FIN_NOCTURNA = '06:00:00';

    public function __construct(
        private readonly AjusteSalarialContratacionService $ajusteSalarialService
    ) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Nomina::with(self::WITH)
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

    public function store(array $data): Nomina
    {
        return DB::transaction(function () use ($data) {
            $nomina = Nomina::create($data);

            Log::info('Nómina creada', [
                'uuid' => $nomina->uuid,
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
                'uuid' => $nomina->uuid,
                'user_id' => $nomina->user_id,
            ]);

            return $nomina->fresh(self::WITH);
        });
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $nomina = $this->getByUuid($uuid);

            if (LiquidacionRetiro::where('nomina_id', $nomina->id)->exists()) {
                throw new \LogicException('No se puede eliminar una nómina vinculada a una liquidación definitiva.');
            }

            $nomina->comisiones()->update([
                'status' => 'aprobada',
                'nomina_id' => null,
            ]);
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
                'user_id' => $calculo['user_id'],
                'jornada_laboral_id' => $calculo['jornada_laboral_id'],
                'contratacion_id' => $calculo['contratacion_id'],
                'descuento_id' => $calculo['descuento_id'],

                'periodo_inicio' => $calculo['periodo_inicio'],
                'periodo_fin' => $calculo['periodo_fin'],

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

                'salario_neto' => $calculo['salario_neto'],
                'liquidada' => true,
                'fecha_liquidacion' => now(),
            ]);

            Comision::whereIn('id', $calculo['comisiones_ids'])->update([
                'status' => 'aplicada',
                'nomina_id' => $nomina->id,
            ]);

            NovedadRetroactiva::whereIn('id', $calculo['novedades_retroactivas_ids'])->update([
                'status' => 'aplicada',
                'nomina_id' => $nomina->id,
            ]);

            Log::info('Nómina liquidada', [
                'uuid' => $nomina->uuid,
                'user_id' => $calculo['user_id'],
                'periodo' => $calculo['periodo_inicio'].' → '.$calculo['periodo_fin'],
                'total_devengado' => $calculo['total_devengado'],
                'total_deducciones' => $calculo['total_deducciones'],
                'salario_neto' => $calculo['salario_neto'],
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
        $fin = Carbon::parse($data['periodo_fin'])->endOfDay();

        $contratacion = Contratacion::where('users_id', $userId)
            ->where('status', 1)
            ->latest('inicio_contratacion')
            ->firstOrFail();
        $configuracion = $this->configuracionActual();

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
        $horasMensualesJornada = $this->horasMensualesJornada($jornada);
        $horasEsperadasPeriodo = round($horasMensualesJornada * ($diasLiquidables / 30), 2);
        $minutosEsperados = $horasEsperadasPeriodo * 60;
        $horasNormales = round(min($ordinariosMinutos, $minutosEsperados) / 60, 2);
        $minutosExtrasDetectados = max(0, $ordinariosMinutos - $minutosEsperados);
        $minutosNocturnosTrabajados = $this->minutosNocturnosTrabajados($sessions);
        $minutosExtrasNocturnosDetectados = min($minutosExtrasDetectados, $minutosNocturnosTrabajados);
        $minutosExtrasDiurnosDetectados = max(0, $minutosExtrasDetectados - $minutosExtrasNocturnosDetectados);
        $horasExtrasDiurnasDetectadas = round($minutosExtrasDiurnosDetectados / 60, 2);
        $horasExtrasNocturnasDetectadas = round($minutosExtrasNocturnosDetectados / 60, 2);
        $horasFestivasTotal = round(($festivoMinutos + $sabadoMinutos) / 60, 2);

        $extrasAprobadas = HoraExtra::where('user_id', $userId)
            ->whereBetween('fecha', [$inicioLiquidable->toDateString(), $finLiquidable->toDateString()])
            ->where('status', 'aprobada')
            ->get();

        $horasExtrasDiurnasAprobadas = round((float) $extrasAprobadas->where('tipo', 'diurna')->sum('horas'), 2);
        $horasExtrasDiurnas = max($horasExtrasDiurnasAprobadas, $horasExtrasDiurnasDetectadas);
        $horasExtrasNocturnasAprobadas = round((float) $extrasAprobadas->where('tipo', 'nocturna')->sum('horas'), 2);
        $horasExtrasNocturnas = max($horasExtrasNocturnasAprobadas, $horasExtrasNocturnasDetectadas);
        $horasNocturnasFestivas = round((float) $extrasAprobadas->where('tipo', 'nocturna_festiva')->sum('horas'), 2);
        $horasFestivasTotal = round($horasFestivasTotal + (float) $extrasAprobadas->where('tipo', 'festiva')->sum('horas'), 2);

        if ($horasExtrasDiurnasDetectadas > $horasExtrasDiurnasAprobadas) {
            $advertencias[] = "Se detectaron {$horasExtrasDiurnasDetectadas} horas extra diurnas desde asistencia por exceder la jornada del período.";
        }

        if ($horasExtrasNocturnasDetectadas > $horasExtrasNocturnasAprobadas) {
            $advertencias[] = "Se detectaron {$horasExtrasNocturnasDetectadas} horas extra nocturnas desde asistencia por exceder la jornada después de las 7:00 p. m.";
        }

        $salarioMensual = (float) $baseSalarial['salario_mensual'];
        $valorDia = round($salarioMensual / 30, 6);
        $valorConfigurado = Valor::where('status', true)->latest()->first();
        $valorHoraCalculado = round($salarioMensual / $horasMensualesJornada, 2);
        $valorHoraBase = max($valorHoraCalculado, (float) ($valorConfigurado?->valor_hora_normal ?? 0));
        $valorHoraNocturna = max(
            round($valorHoraBase * (1 + self::RECARGO_EXTRA_NOCTURNA), 2),
            (float) ($valorConfigurado?->valor_hora_nocturna ?? 0)
        );
        $valorHoraDominical = max(
            round($valorHoraBase * (1 + self::RECARGO_FESTIVA), 2),
            (float) ($valorConfigurado?->valor_hora_dominical ?? 0)
        );
        $valorHoraDominicalExtra = max(
            round($valorHoraBase * (1 + self::RECARGO_NOCTURNA_FESTIVA), 2),
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

        $minutosNoRemunerados = $permisosNoRemunerados->sum(fn ($p) => Carbon::parse($p->hora_inicio)->diffInMinutes(Carbon::parse($p->hora_fin))
        );

        $valorPermisosNoRemunerados = round(($minutosNoRemunerados / 60) * $valorHoraBase, 2);
        $salarioBaseSinIncapacidad = round(($valorDia * $diasLiquidables) + ($valorDia * $diasVacacionesCompensadas), 2);
        $valorIncapacidadReconocido = round($valorDia * $diasIncapacidad * self::PORCENTAJE_INCAPACIDAD, 2);
        $deduccionIncapacidad = round($valorDia * $diasIncapacidad * (1 - self::PORCENTAJE_INCAPACIDAD), 2);
        $salarioBasePeriodo = round(($valorDia * max(0, $diasLiquidables - $diasIncapacidad))
            + $valorIncapacidadReconocido
            + ($valorDia * $diasVacacionesCompensadas), 2);
        $auxilioTransportePeriodo = round((float) $baseSalarial['auxilio_transporte'] * ($diasLiquidables / 30), 2);
        $pagoNoPrestacionalPeriodo = round((float) $baseSalarial['no_salarial'] * ($diasLiquidables / 30), 2);
        $comisiones = Comision::where('user_id', $userId)
            ->where('status', 'aprobada')
            ->whereDate('periodo_inicio', $inicio->toDateString())
            ->whereDate('periodo_fin', $fin->toDateString())
            ->get();
        $totalComisiones = round((float) $comisiones->sum('valor'), 2);
        $novedadesRetroactivas = $this->calcularNovedadesRetroactivas($userId, $inicioLiquidable, $finLiquidable);

        // El salario mensual ya remunera las horas ordinarias; se guardan para control, no se suman otra vez.
        $valorHorasNormales = 0;
        $valorHorasExtrasDiurnas = round($horasExtrasDiurnas * $valorHoraBase * (1 + self::RECARGO_EXTRA_DIURNA), 2);
        $valorHorasExtrasNocturnas = round($horasExtrasNocturnas * $valorHoraBase * (1 + self::RECARGO_EXTRA_NOCTURNA), 2);
        $valorHorasFestivas = round($horasFestivasTotal * $valorHoraBase * (1 + self::RECARGO_FESTIVA), 2);
        $valorHorasNocturnasFestivas = round($horasNocturnasFestivas * $valorHoraBase * (1 + self::RECARGO_NOCTURNA_FESTIVA), 2);

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

        $porcentajeSalud = ((float) $configuracion->porcentaje_salud_empleado) / 100;
        $porcentajePension = ((float) $configuracion->porcentaje_pension_empleado) / 100;
        $deduccionSalud = round($baseParaDeducciones * $porcentajeSalud, 2);
        $deduccionPension = round($baseParaDeducciones * $porcentajePension, 2);
        $descuentosNomina = $this->calcularDescuentos($userId, $inicioLiquidable, $finLiquidable, $data['descuento_id'] ?? null);
        $totalDescuentosAdicionales = round($descuentosNomina['valor'] + $valorPermisosNoRemunerados + $novedadesRetroactivas['deducciones'], 2);
        $totalDeducciones = round($deduccionSalud + $deduccionPension + $totalDescuentosAdicionales, 2);
        $salarioNeto = round($totalDevengado - $totalDeducciones, 2);

        return [
            'user_id' => $userId,
            'contratacion_id' => $contratacion->id,
            'jornada_laboral_id' => $jornada->id,
            'descuento_id' => $descuentosNomina['descuento_id'],
            'periodo_inicio' => $inicio->toDateString(),
            'periodo_fin' => $fin->toDateString(),
            'dias_liquidados' => $diasLiquidables,
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
            'porcentaje_salud_empleado' => (float) $configuracion->porcentaje_salud_empleado,
            'porcentaje_pension_empleado' => (float) $configuracion->porcentaje_pension_empleado,
            'total_descuentos_adicionales' => $totalDescuentosAdicionales,
            'total_deducciones' => $totalDeducciones,
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

    private function minutosNocturnosTrabajados($sessions): int
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

            $minutos = $this->minutosNocturnosEntre($entrada, $salida);
            $minutos -= $this->minutosNocturnosDeDescanso($session->hora_salida_brake, $session->hora_ingreso_brake);
            $minutos -= $this->minutosNocturnosDeDescanso($session->hora_salida_almuerzo, $session->hora_ingreso_almuerzo);

            $total += max(0, $minutos);
        }

        return max(0, $total);
    }

    private function minutosNocturnosDeDescanso($inicio, $fin): int
    {
        if (! $inicio || ! $fin) {
            return 0;
        }

        $desde = Carbon::parse($inicio);
        $hasta = Carbon::parse($fin);
        if ($hasta->lessThanOrEqualTo($desde)) {
            $hasta->addDay();
        }

        return $this->minutosNocturnosEntre($desde, $hasta);
    }

    private function minutosNocturnosEntre(Carbon $inicio, Carbon $fin): int
    {
        $total = 0;
        $cursor = $inicio->copy()->startOfDay();

        while ($cursor->lte($fin)) {
            $inicioNoche = Carbon::parse($cursor->toDateString().' '.self::HORA_INICIO_NOCTURNA);
            $finNoche = Carbon::parse($cursor->copy()->addDay()->toDateString().' '.self::HORA_FIN_NOCTURNA);
            $desde = $inicio->copy()->max($inicioNoche);
            $hasta = $fin->copy()->min($finNoche);

            if ($desde->lt($hasta)) {
                $total += (int) $desde->diffInMinutes($hasta);
            }

            $cursor->addDay();
        }

        return $total;
    }

    private function configuracionActual(): ConfiguracionNomina
    {
        return ConfiguracionNomina::where('status', true)
            ->latest()
            ->first()
            ?? ConfiguracionNomina::create([
                'nombre' => 'Configuración general',
                'porcentaje_salud_empleado' => 4,
                'porcentaje_pension_empleado' => 4,
                'status' => true,
            ]);
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

    private function diasComercialesMes(Carbon $inicio, Carbon $fin): int
    {
        $ultimoDiaMes = $fin->copy()->endOfMonth()->day;
        $diaInicio = min($inicio->day, 30);
        $diaFin = $fin->day === $ultimoDiaMes ? 30 : min($fin->day, 30);

        return max(1, $diaFin - $diaInicio + 1);
    }
}
