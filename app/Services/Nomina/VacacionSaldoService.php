<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Contratacion;
use App\Models\Nomina\Vacacion;
use Carbon\Carbon;

class VacacionSaldoService
{
    public function contratoActivo(int $userId): ?Contratacion
    {
        return Contratacion::where('users_id', $userId)
            ->where('status', true)
            ->latest('inicio_contratacion')
            ->first();
    }

    public function resumen(
        int $userId,
        ?Carbon $fechaCorte = null,
        ?int $excluirVacacionId = null,
        bool $bloquear = false,
    ): array {
        $fecha = ($fechaCorte ?? now())->copy()->endOfDay();
        $contrato = $this->contratoActivo($userId);

        if (! $contrato) {
            return $this->sinContrato($userId, $fecha);
        }

        if ($bloquear) {
            $contrato = Contratacion::whereKey($contrato->id)->lockForUpdate()->firstOrFail();
        }

        $inicio = Carbon::parse($contrato->inicio_contratacion)->startOfDay();
        $fin = $fecha->copy();

        if ($contrato->fin_contrato) {
            $fin = $fin->min(Carbon::parse($contrato->fin_contrato)->endOfDay());
        }

        $diasTrabajados = $this->diasComerciales($inicio, $fin);
        $diasGanados = round($diasTrabajados * 15 / 360, 4);
        $diasVacacionesIniciales = (float) ($contrato->dias_vacaciones_iniciales ?? 0);

        $vacaciones = Vacacion::query()
            ->with('liquidacionPrestacion:id,vacacion_id')
            ->where('user_id', $userId)
            ->where(function ($query) use ($contrato, $inicio) {
                $query->where('contratacion_id', $contrato->id)
                    ->orWhere(function ($legacy) use ($inicio) {
                        $legacy->whereNull('contratacion_id')
                            ->whereDate('fecha_inicio', '>=', $inicio->toDateString());
                    });
            })
            ->when($excluirVacacionId, fn ($query) => $query->where('id', '!=', $excluirVacacionId))
            ->get();

        $ordinariasAprobadas = (float) $vacaciones
            ->where('status', 'aprobada')
            ->where('tipo', 'ordinarias')
            ->sum('dias_habiles');

        $compensadasLiquidadas = (float) $vacaciones
            ->where('status', 'aprobada')
            ->where('tipo', 'compensadas')
            ->filter(fn (Vacacion $vacacion) => $vacacion->liquidacionPrestacion !== null)
            ->sum('dias_habiles');

        $compensadasPorLiquidar = (float) $vacaciones
            ->where('status', 'aprobada')
            ->where('tipo', 'compensadas')
            ->filter(fn (Vacacion $vacacion) => $vacacion->liquidacionPrestacion === null)
            ->sum('dias_habiles');

        $pendientes = (float) $vacaciones
            ->where('status', 'pendiente')
            ->sum('dias_habiles');

        $disfrutados = $diasVacacionesIniciales + $ordinariasAprobadas;
        $consumidos = $disfrutados + $compensadasLiquidadas;
        $comprometidos = $compensadasPorLiquidar + $pendientes;

        return [
            'user_id' => $userId,
            'empleado' => $contrato->usuario()->select('id', 'name', 'email')->first(),
            'contratacion_id' => $contrato->id,
            'tiene_contrato_activo' => true,
            'fecha_corte' => $fecha->toDateString(),
            'inicio_contratacion' => $inicio->toDateString(),
            'dias_trabajados' => $diasTrabajados,
            'dias_ganados' => $diasGanados,
            'dias_vacaciones_iniciales' => $diasVacacionesIniciales,
            'dias_disfrutados' => $disfrutados,
            'dias_disfrutados_registrados' => $ordinariasAprobadas,
            'dias_compensados' => $compensadasLiquidadas,
            'dias_compensados_por_liquidar' => $compensadasPorLiquidar,
            'dias_pendientes_solicitados' => $pendientes,
            'dias_usados' => $consumidos,
            'dias_comprometidos' => $comprometidos,
            'dias_disponibles' => round(max(0, $diasGanados - $consumidos - $comprometidos), 4),
        ];
    }

    public function diasComerciales(Carbon $inicio, Carbon $fin): int
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

    private function sinContrato(int $userId, Carbon $fecha): array
    {
        return [
            'user_id' => $userId,
            'empleado' => null,
            'contratacion_id' => null,
            'tiene_contrato_activo' => false,
            'fecha_corte' => $fecha->toDateString(),
            'inicio_contratacion' => null,
            'dias_trabajados' => 0,
            'dias_ganados' => 0,
            'dias_vacaciones_iniciales' => 0,
            'dias_disfrutados' => 0,
            'dias_disfrutados_registrados' => 0,
            'dias_compensados' => 0,
            'dias_compensados_por_liquidar' => 0,
            'dias_pendientes_solicitados' => 0,
            'dias_usados' => 0,
            'dias_comprometidos' => 0,
            'dias_disponibles' => 0,
        ];
    }
}
