<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Contratacion;
use App\Models\Nomina\HistorialSalarialContratacion;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AjusteSalarialContratacionService
{
    private const WITH = [
        'contratacion.usuario:id,name,email',
        'contratacion.empresa:id,nombre',
        'empleado:id,name,email',
        'aprobador:id,name,email',
    ];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return HistorialSalarialContratacion::with(self::WITH)
            ->when(! empty($filters['contratacion_id']), fn ($query) => $query->where('contratacion_id', $filters['contratacion_id']))
            ->when(! empty($filters['user_id']), fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when(! empty($filters['tipo_ajuste']), fn ($query) => $query->where('tipo_ajuste', $filters['tipo_ajuste']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', filter_var($filters['status'], FILTER_VALIDATE_BOOL)))
            ->when(! empty($filters['anio']), fn ($query) => $query->whereYear('fecha_vigencia', $filters['anio']))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('motivo', 'like', "%{$search}%")
                        ->orWhere('tipo_ajuste', 'like', "%{$search}%")
                        ->orWhereHas('empleado', fn ($empleado) => $empleado
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('contratacion', fn ($contrato) => $contrato
                            ->where('numero_documento', 'like', "%{$search}%")
                            ->orWhere('cargo', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('fecha_vigencia')
            ->orderByDesc('id')
            ->paginate(min(max((int) ($filters['per_page'] ?? 15), 1), 100));
    }

    public function getByUuid(string $uuid): HistorialSalarialContratacion
    {
        return HistorialSalarialContratacion::with(self::WITH)->where('uuid', $uuid)->firstOrFail();
    }

    public function store(array $data): HistorialSalarialContratacion
    {
        return DB::transaction(function () use ($data) {
            $contratacion = Contratacion::lockForUpdate()->findOrFail($data['contratacion_id']);
            $vigencia = Carbon::parse($data['fecha_vigencia'])->startOfDay();
            $baseAnterior = $this->salarioVigente($contratacion, $vigencia->copy()->subDay());

            $ajuste = HistorialSalarialContratacion::create([
                'contratacion_id' => $contratacion->id,
                'user_id' => $contratacion->users_id,
                'tipo_ajuste' => $data['tipo_ajuste'],
                'salario_anterior' => $baseAnterior['salario_mensual'],
                'salario_nuevo' => round((float) $data['salario_nuevo'], 2),
                'auxilio_anterior' => $baseAnterior['auxilio_transporte'],
                'auxilio_nuevo' => round((float) ($data['auxilio_nuevo'] ?? $baseAnterior['auxilio_transporte']), 2),
                'no_salarial_anterior' => $baseAnterior['no_salarial'],
                'no_salarial_nuevo' => round((float) ($data['no_salarial_nuevo'] ?? $baseAnterior['no_salarial']), 2),
                'porcentaje_aumento' => isset($data['porcentaje_aumento']) ? round((float) $data['porcentaje_aumento'], 4) : null,
                'fecha_vigencia' => $vigencia->toDateString(),
                'motivo' => $data['motivo'] ?? null,
                'observacion' => $data['observacion'] ?? null,
                'aprobado_por' => Auth::id(),
                'status' => $data['status'] ?? true,
            ]);

            $this->sincronizarContratoActual($contratacion);

            return $ajuste->load(self::WITH);
        });
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $ajuste = $this->getByUuid($uuid);
            $contratacion = Contratacion::lockForUpdate()->findOrFail($ajuste->contratacion_id);

            $ajuste->delete();
            $this->sincronizarContratoActual($contratacion, $ajuste);
        });
    }

    public function salarioVigente(Contratacion $contratacion, Carbon|string $fecha): array
    {
        $fecha = $fecha instanceof Carbon ? $fecha->copy()->startOfDay() : Carbon::parse($fecha)->startOfDay();

        $ajuste = HistorialSalarialContratacion::where('contratacion_id', $contratacion->id)
            ->where('status', true)
            ->whereDate('fecha_vigencia', '<=', $fecha->toDateString())
            ->orderByDesc('fecha_vigencia')
            ->orderByDesc('id')
            ->first();

        if ($ajuste) {
            return [
                'salario_mensual' => (float) $ajuste->salario_nuevo,
                'auxilio_transporte' => (float) $ajuste->auxilio_nuevo,
                'no_salarial' => (float) $ajuste->no_salarial_nuevo,
                'ajuste_uuid' => $ajuste->uuid,
                'fecha_vigencia' => $ajuste->fecha_vigencia?->toDateString(),
            ];
        }

        $primerAjustePosterior = HistorialSalarialContratacion::where('contratacion_id', $contratacion->id)
            ->where('status', true)
            ->whereDate('fecha_vigencia', '>', $fecha->toDateString())
            ->orderBy('fecha_vigencia')
            ->orderBy('id')
            ->first();

        if ($primerAjustePosterior) {
            return [
                'salario_mensual' => (float) $primerAjustePosterior->salario_anterior,
                'auxilio_transporte' => (float) $primerAjustePosterior->auxilio_anterior,
                'no_salarial' => (float) $primerAjustePosterior->no_salarial_anterior,
                'ajuste_uuid' => null,
                'fecha_vigencia' => $contratacion->inicio_contratacion?->toDateString(),
            ];
        }

        return [
            'salario_mensual' => (float) $contratacion->base_salario,
            'auxilio_transporte' => (float) $contratacion->auxilio_transporte,
            'no_salarial' => (float) $contratacion->no_salarial,
            'ajuste_uuid' => null,
            'fecha_vigencia' => $contratacion->inicio_contratacion?->toDateString(),
        ];
    }

    public function salarioPromedioPeriodo(Contratacion $contratacion, Carbon|string $inicio, Carbon|string $fin): array
    {
        $inicio = $inicio instanceof Carbon ? $inicio->copy()->startOfDay() : Carbon::parse($inicio)->startOfDay();
        $fin = $fin instanceof Carbon ? $fin->copy()->startOfDay() : Carbon::parse($fin)->startOfDay();

        if ($inicio->gt($fin)) {
            return $this->salarioVigente($contratacion, $fin);
        }

        $cambios = HistorialSalarialContratacion::where('contratacion_id', $contratacion->id)
            ->where('status', true)
            ->whereDate('fecha_vigencia', '>', $inicio->toDateString())
            ->whereDate('fecha_vigencia', '<=', $fin->toDateString())
            ->orderBy('fecha_vigencia')
            ->orderBy('id')
            ->get();

        $cursor = $inicio->copy();
        $totalDias = 0;
        $salario = 0;
        $auxilio = 0;
        $noSalarial = 0;
        $tramos = [];

        foreach ($cambios as $cambio) {
            $segmentoFin = Carbon::parse($cambio->fecha_vigencia)->subDay()->startOfDay();
            if ($cursor->lte($segmentoFin)) {
                [$totalDias, $salario, $auxilio, $noSalarial, $tramos] = $this->acumularTramo(
                    $contratacion,
                    $cursor,
                    $segmentoFin,
                    $totalDias,
                    $salario,
                    $auxilio,
                    $noSalarial,
                    $tramos
                );
            }
            $cursor = Carbon::parse($cambio->fecha_vigencia)->startOfDay();
        }

        if ($cursor->lte($fin)) {
            [$totalDias, $salario, $auxilio, $noSalarial, $tramos] = $this->acumularTramo(
                $contratacion,
                $cursor,
                $fin,
                $totalDias,
                $salario,
                $auxilio,
                $noSalarial,
                $tramos
            );
        }

        if ($totalDias <= 0) {
            return $this->salarioVigente($contratacion, $fin);
        }

        return [
            'salario_mensual' => round($salario / $totalDias, 2),
            'auxilio_transporte' => round($auxilio / $totalDias, 2),
            'no_salarial' => round($noSalarial / $totalDias, 2),
            'dias' => $totalDias,
            'tramos' => $tramos,
        ];
    }

    private function acumularTramo(
        Contratacion $contratacion,
        Carbon $inicio,
        Carbon $fin,
        int $totalDias,
        float $salario,
        float $auxilio,
        float $noSalarial,
        array $tramos,
    ): array {
        $vigente = $this->salarioVigente($contratacion, $inicio);
        $dias = $inicio->diffInDays($fin) + 1;

        $totalDias += $dias;
        $salario += $vigente['salario_mensual'] * $dias;
        $auxilio += $vigente['auxilio_transporte'] * $dias;
        $noSalarial += $vigente['no_salarial'] * $dias;
        $tramos[] = [
            'desde' => $inicio->toDateString(),
            'hasta' => $fin->toDateString(),
            'dias' => $dias,
            'salario_mensual' => $vigente['salario_mensual'],
            'auxilio_transporte' => $vigente['auxilio_transporte'],
            'no_salarial' => $vigente['no_salarial'],
            'ajuste_uuid' => $vigente['ajuste_uuid'] ?? null,
        ];

        return [$totalDias, $salario, $auxilio, $noSalarial, $tramos];
    }

    private function sincronizarContratoActual(Contratacion $contratacion, ?HistorialSalarialContratacion $ajusteEliminado = null): void
    {
        $actual = HistorialSalarialContratacion::where('contratacion_id', $contratacion->id)
            ->where('status', true)
            ->whereDate('fecha_vigencia', '<=', now()->toDateString())
            ->orderByDesc('fecha_vigencia')
            ->orderByDesc('id')
            ->first();

        if ($actual) {
            $contratacion->update([
                'base_salario' => $actual->salario_nuevo,
                'auxilio_transporte' => $actual->auxilio_nuevo,
                'no_salarial' => $actual->no_salarial_nuevo,
            ]);

            return;
        }

        if ($ajusteEliminado) {
            $contratacion->update([
                'base_salario' => $ajusteEliminado->salario_anterior,
                'auxilio_transporte' => $ajusteEliminado->auxilio_anterior,
                'no_salarial' => $ajusteEliminado->no_salarial_anterior,
            ]);
        }
    }
}
