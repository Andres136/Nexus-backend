<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Contratacion;
use App\Models\Nomina\Descuento;
use App\Models\Nomina\HoraExtra;
use App\Models\Nomina\JornadaLaboral;
use App\Models\Nomina\Permiso;
use App\Models\Nomina\Nomina;
use App\Models\Nomina\Valor;
use App\Models\Nomina\WorkSession;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NominaService
{
    private const WITH = [
        'empleado',
        'contratacion',
        'descuento',
        'jornadaLaboral',
        'transacionalRegistro',
    ];

    // Porcentajes de ley colombiana sobre la hora normal
    private const RECARGO_EXTRA_DIURNA     = 0.25; // +25%
    private const RECARGO_EXTRA_NOCTURNA   = 0.75; // +75%
    private const RECARGO_FESTIVA          = 0.75; // +75%
    private const RECARGO_NOCTURNA_FESTIVA = 1.10; // +110%

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Nomina::with(self::WITH)
            ->when(!empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['jornada_laboral_id']), fn($q) =>
                $q->where('jornada_laboral_id', $filters['jornada_laboral_id']))
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
            $userId = $data['user_id'];
            $inicio = Carbon::parse($data['periodo_inicio'])->startOfDay();
            $fin    = Carbon::parse($data['periodo_fin'])->endOfDay();

            // Contrato activo del empleado
            $contratacion = Contratacion::where('users_id', $userId)
                ->where('status', 1)
                ->latest('inicio_contratacion')
                ->firstOrFail();

            // Tarifas de hora vigentes (la más reciente activa)
            $valor = Valor::where('status', true)->latest()->firstOrFail();

            // Jornada laboral del período
            $jornada = JornadaLaboral::findOrFail($data['jornada_laboral_id']);

            // ── Horas ordinarias desde WorkSessions ──────────────────────
            $sessions = WorkSession::where('user_id', $userId)
                ->whereBetween('registro_diario', [$inicio->toDateString(), $fin->toDateString()])
                ->get();

            $totalMinutos   = (int) $sessions->sum('minutos_trabajados');
            $festivoMinutos = (float) $sessions->sum('festivo_minutos');
            $sabadoMinutos  = (float) $sessions->sum('sabado_minutos');
            $ordinariosMinutos = max(0, $totalMinutos - $festivoMinutos - $sabadoMinutos);

            // Horas normales = minutos ordinarios limitados a la jornada esperada
            $diasHabiles      = $this->contarDiasHabiles($inicio, $fin);
            $minutosEsperados = ($jornada->horas_semanales / 5) * $diasHabiles * 60;
            $horasNormales    = round(min($ordinariosMinutos, $minutosEsperados) / 60, 2);

            $horasFestivasTotal = round(($festivoMinutos + $sabadoMinutos) / 60, 2);

            // ── Horas extras desde autorizaciones aprobadas ───────────────
            $extrasAprobadas = HoraExtra::where('user_id', $userId)
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                ->where('status', 'aprobada')
                ->get();

            $horasExtrasDiurnas     = round((float) $extrasAprobadas->where('tipo', 'diurna')->sum('horas'), 2);
            $horasExtrasNocturnas   = round((float) $extrasAprobadas->where('tipo', 'nocturna')->sum('horas'), 2);
            $horasNocturnasFestivas = round((float) $extrasAprobadas->where('tipo', 'nocturna_festiva')->sum('horas'), 2);

            // Horas festivas autorizadas se suman a las detectadas en WorkSession
            $horasFestivasExtra = round((float) $extrasAprobadas->where('tipo', 'festiva')->sum('horas'), 2);
            $horasFestivasTotal = round($horasFestivasTotal + $horasFestivasExtra, 2);

            // ── Permisos no remunerados aprobados ────────────────────────
            // Se suman los minutos de permiso no remunerado y se restan de las horas normales pagadas
            $permisosNoRemunerados = Permiso::where('user_id', $userId)
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                ->where('status', 'aprobado')
                ->where('es_remunerado', false)
                ->get();

            $minutosNoRemunerados = $permisosNoRemunerados->sum(fn($p) =>
                Carbon::parse($p->hora_inicio)->diffInMinutes(Carbon::parse($p->hora_fin))
            );

            // Descontar del cómputo de horas normales
            $horasNormales = round(max(0, $horasNormales - $minutosNoRemunerados / 60), 2);

            // ── Devengados ────────────────────────────────────────────────
            $diasPeriodo = $inicio->diffInDays($fin) + 1;

            // Salario proporcional al período (base colombiana: mes = 30 días)
            $salarioBasePeriodo = round((float) $contratacion->base_salario * ($diasPeriodo / 30), 2);

            // Auxilio de transporte proporcional
            $auxilioTransportePeriodo = round((float) $contratacion->auxilio_transporte * ($diasPeriodo / 30), 2);

            // Valores por tipo de hora
            $valorHorasNormales            = round($horasNormales * (float) $valor->valor_hora_normal, 2);
            $valorHorasExtrasDiurnas       = round($horasExtrasDiurnas * (float) $valor->valor_hora_normal * (1 + self::RECARGO_EXTRA_DIURNA), 2);
            $valorHorasExtrasNocturnas     = round($horasExtrasNocturnas * (float) $valor->valor_hora_nocturna * (1 + self::RECARGO_EXTRA_NOCTURNA), 2);
            $valorHorasFestivas            = round($horasFestivasTotal * (float) $valor->valor_hora_dominical, 2);
            $valorHorasNocturnasFestivas   = round($horasNocturnasFestivas * (float) $valor->valor_hora_dominical_extra, 2);

            $totalDevengado = $salarioBasePeriodo
                + $auxilioTransportePeriodo
                + $valorHorasNormales
                + $valorHorasExtrasDiurnas
                + $valorHorasExtrasNocturnas
                + $valorHorasFestivas
                + $valorHorasNocturnasFestivas;

            // ── Deducciones ───────────────────────────────────────────────
            // Base para deducciones: salario devengado sin auxilio de transporte
            $baseParaDeducciones = $salarioBasePeriodo
                + $valorHorasNormales
                + $valorHorasExtrasDiurnas
                + $valorHorasExtrasNocturnas
                + $valorHorasFestivas
                + $valorHorasNocturnasFestivas;

            $deduccionSalud   = round($baseParaDeducciones * 0.04, 2);
            $deduccionPension = round($baseParaDeducciones * 0.04, 2);

            // Cuota del descuento de libranza del período
            $cuotaDescuento = 0;
            if (!empty($data['descuento_id'])) {
                $descuento = Descuento::find($data['descuento_id']);
                if ($descuento && $descuento->status) {
                    $cuotaDescuento = (float) ($descuento->valor_cuota ?? 0);
                }
            }

            $totalDeducciones = $deduccionSalud + $deduccionPension + $cuotaDescuento;
            $salarioNeto      = round($totalDevengado - $totalDeducciones, 2);

            // ── Persistencia ──────────────────────────────────────────────
            $nomina = Nomina::create([
                'user_id'            => $userId,
                'jornada_laboral_id' => $data['jornada_laboral_id'],
                'contratacion_id'    => $contratacion->id,
                'descuento_id'       => $data['descuento_id'] ?? null,

                'periodo_inicio' => $inicio->toDateString(),
                'periodo_fin'    => $fin->toDateString(),

                'horas_normales'          => $horasNormales,
                'horas_extras_nocturnas'  => $horasExtrasNocturnas,
                'horas_extras_diurnas'    => $horasExtrasDiurnas,
                'horas_festivas'          => $horasFestivasTotal,
                'horas_nocturnas_festivas'=> $horasNocturnasFestivas,

                'valor_hora_normal'          => $valor->valor_hora_normal,
                'valor_hora_nocturna'        => $valor->valor_hora_nocturna,
                'valor_hora_dominical'       => $valor->valor_hora_dominical,
                'valor_hora_dominical_extra' => $valor->valor_hora_dominical_extra,

                'salario_base_devengado'         => $salarioBasePeriodo,
                'auxilio_transporte'             => $auxilioTransportePeriodo,
                'valor_horas_normales'           => $valorHorasNormales,
                'valor_horas_extras_nocturnas'   => $valorHorasExtrasNocturnas,
                'valor_horas_extras_diurnas'     => $valorHorasExtrasDiurnas,
                'valor_horas_festivas'           => $valorHorasFestivas,
                'valor_horas_nocturnas_festivas' => $valorHorasNocturnasFestivas,
                'total_devengado'               => $totalDevengado,

                'deduccion_salud'              => $deduccionSalud,
                'deduccion_pension'            => $deduccionPension,
                'total_descuentos_adicionales' => $cuotaDescuento,
                'total_deducciones'            => $totalDeducciones,

                'salario_neto'       => $salarioNeto,
                'liquidada'          => true,
                'fecha_liquidacion'  => now(),
            ]);

            Log::info('Nómina liquidada', [
                'uuid'            => $nomina->uuid,
                'user_id'         => $userId,
                'periodo'         => $inicio->toDateString() . ' → ' . $fin->toDateString(),
                'total_devengado' => $totalDevengado,
                'total_deducciones' => $totalDeducciones,
                'salario_neto'    => $salarioNeto,
            ]);

            return $nomina->load(self::WITH);
        });
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
