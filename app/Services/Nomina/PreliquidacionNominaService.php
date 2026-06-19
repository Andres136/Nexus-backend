<?php

namespace App\Services\Nomina;

use App\Models\Nomina\PreliquidacionNomina;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

class PreliquidacionNominaService
{
    private const WITH = [
        'ajustes.creador:id,name,email',
        'generadoPor:id,name,email',
        'revisadoPor:id,name,email',
        'aprobadoPor:id,name,email',
        'nomina:id,uuid,preliquidacion_id',
    ];

    public function __construct(
        private readonly NominaService $nominaService,
    ) {}

    public function guardar(array $data): PreliquidacionNomina
    {
        return DB::transaction(function () use ($data) {
            $calculo = $this->nominaService->preliquidar($data);

            $preliquidacion = PreliquidacionNomina::where('user_id', $data['user_id'])
                ->whereDate('periodo_inicio', $data['periodo_inicio'])
                ->whereDate('periodo_fin', $data['periodo_fin'])
                ->whereIn('estado', ['borrador', 'en_revision', 'rechazada'])
                ->latest()
                ->first();

            if ($preliquidacion) {
                $preliquidacion->ajustes()->delete();
            }

            $valores = [
                'contratacion_id' => $calculo['contratacion_id'],
                'jornada_laboral_id' => $calculo['jornada_laboral_id'],
                'descuento_id' => $calculo['descuento_id'],
                'estado' => 'borrador',
                'calculo_original' => $calculo,
                'calculo_ajustado' => $calculo,
                'total_devengado_original' => $calculo['total_devengado'],
                'total_deducciones_original' => $calculo['total_deducciones'],
                'salario_neto_original' => $calculo['salario_neto'],
                'total_devengado_ajustado' => $calculo['total_devengado'],
                'total_deducciones_ajustado' => $calculo['total_deducciones'],
                'salario_neto_ajustado' => $calculo['salario_neto'],
                'generado_por' => Auth::id(),
                'revisado_por' => null,
                'aprobado_por' => null,
                'fecha_revision' => null,
                'fecha_aprobacion' => null,
                'observacion_revision' => null,
            ];

            if ($preliquidacion) {
                $preliquidacion->update($valores);
            } else {
                $preliquidacion = PreliquidacionNomina::create([
                    ...$valores,
                    'user_id' => $data['user_id'],
                    'periodo_inicio' => $data['periodo_inicio'],
                    'periodo_fin' => $data['periodo_fin'],
                ]);
            }

            return $preliquidacion->fresh(self::WITH);
        });
    }

    public function getByUuid(string $uuid): PreliquidacionNomina
    {
        return PreliquidacionNomina::with(self::WITH)->where('uuid', $uuid)->firstOrFail();
    }

    public function agregarAjuste(string $uuid, array $data): PreliquidacionNomina
    {
        return DB::transaction(function () use ($uuid, $data) {
            $preliquidacion = PreliquidacionNomina::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            $this->validarEditable($preliquidacion);

            $preliquidacion->ajustes()->create([
                ...$data,
                'creado_por' => Auth::id(),
            ]);

            return $this->recalcular($preliquidacion);
        });
    }

    public function eliminarAjuste(string $uuid, string $ajusteUuid): PreliquidacionNomina
    {
        return DB::transaction(function () use ($uuid, $ajusteUuid) {
            $preliquidacion = PreliquidacionNomina::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            $this->validarEditable($preliquidacion);
            $preliquidacion->ajustes()->where('uuid', $ajusteUuid)->firstOrFail()->delete();

            return $this->recalcular($preliquidacion);
        });
    }

    public function enviarRevision(string $uuid, ?string $observacion): PreliquidacionNomina
    {
        $preliquidacion = PreliquidacionNomina::where('uuid', $uuid)->firstOrFail();
        $this->validarEditable($preliquidacion);
        $preliquidacion->update([
            'estado' => 'en_revision',
            'revisado_por' => Auth::id(),
            'fecha_revision' => now(),
            'observacion_revision' => $observacion,
        ]);

        return $preliquidacion->fresh(self::WITH);
    }

    public function aprobar(string $uuid, ?string $observacion): PreliquidacionNomina
    {
        $preliquidacion = PreliquidacionNomina::where('uuid', $uuid)->firstOrFail();
        if (! in_array($preliquidacion->estado, ['borrador', 'en_revision'], true)) {
            throw new LogicException('Esta preliquidación no está disponible para aprobación.');
        }

        $preliquidacion->update([
            'estado' => 'aprobada',
            'revisado_por' => $preliquidacion->revisado_por ?: Auth::id(),
            'fecha_revision' => $preliquidacion->fecha_revision ?: now(),
            'aprobado_por' => Auth::id(),
            'fecha_aprobacion' => now(),
            'observacion_revision' => $observacion ?? $preliquidacion->observacion_revision,
        ]);

        return $preliquidacion->fresh(self::WITH);
    }

    public function liquidar(string $uuid)
    {
        return DB::transaction(function () use ($uuid) {
            $preliquidacion = PreliquidacionNomina::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            if ($preliquidacion->estado !== 'aprobada') {
                throw new LogicException('La preliquidación debe estar aprobada antes de liquidar.');
            }

            $nomina = $this->nominaService->liquidarCalculoAprobado(
                $preliquidacion->calculo_ajustado,
                $preliquidacion->id
            );
            $preliquidacion->update(['estado' => 'liquidada']);

            return $nomina;
        });
    }

    private function validarEditable(PreliquidacionNomina $preliquidacion): void
    {
        if (! in_array($preliquidacion->estado, ['borrador', 'en_revision'], true)) {
            throw new LogicException('La preliquidación ya no admite ajustes.');
        }
    }

    private function recalcular(PreliquidacionNomina $preliquidacion): PreliquidacionNomina
    {
        $preliquidacion->load('ajustes');
        $calculo = $preliquidacion->calculo_original;
        $devengos = (float) $preliquidacion->ajustes->where('tipo', 'devengo')->sum('valor');
        $deducciones = (float) $preliquidacion->ajustes->where('tipo', 'deduccion')->sum('valor');
        $baseAdicional = (float) $preliquidacion->ajustes
            ->where('tipo', 'devengo')
            ->where('afecta_base_aportes', true)
            ->sum('valor');

        $calculo['ajustes_revision'] = $preliquidacion->ajustes->map(fn ($ajuste) => [
            'uuid' => $ajuste->uuid,
            'tipo' => $ajuste->tipo,
            'concepto' => $ajuste->concepto,
            'valor' => (float) $ajuste->valor,
            'afecta_base_aportes' => $ajuste->afecta_base_aportes,
            'motivo' => $ajuste->motivo,
            'creado_por' => $ajuste->creado_por,
        ])->values()->all();
        $calculo['total_ajustes_devengo'] = round($devengos, 2);
        $calculo['total_ajustes_deduccion'] = round($deducciones, 2);
        $calculo['total_devengado'] = round((float) $calculo['total_devengado'] + $devengos, 2);
        $calculo['total_deducciones'] = round((float) $calculo['total_deducciones'] + $deducciones, 2);
        $calculo['salario_neto'] = round($calculo['total_devengado'] - $calculo['total_deducciones'], 2);

        if ($baseAdicional > 0) {
            $calculo['base_aportes_empleador'] = round((float) $calculo['base_aportes_empleador'] + $baseAdicional, 2);
            foreach ([
                'salud_empleador' => 'porcentaje_salud_empleador',
                'pension_empleador' => 'porcentaje_pension_empleador',
                'arl' => 'porcentaje_arl',
                'sena' => 'porcentaje_sena',
                'icbf' => 'porcentaje_icbf',
                'caja_compensacion' => 'porcentaje_caja_compensacion',
            ] as $costo => $porcentaje) {
                $calculo["costo_{$costo}"] = round(
                    $calculo['base_aportes_empleador'] * ((float) $calculo[$porcentaje] / 100),
                    2
                );
            }
            $calculo['costo_parafiscales'] = round(
                $calculo['costo_sena'] + $calculo['costo_icbf'] + $calculo['costo_caja_compensacion'],
                2
            );
            $calculo['costo_total_empleador'] = round(
                $calculo['total_devengado']
                + $calculo['costo_salud_empleador']
                + $calculo['costo_pension_empleador']
                + $calculo['costo_arl']
                + $calculo['costo_parafiscales'],
                2
            );
        } else {
            $calculo['costo_total_empleador'] = round((float) $calculo['costo_total_empleador'] + $devengos, 2);
        }

        $preliquidacion->update([
            'calculo_ajustado' => $calculo,
            'total_devengado_ajustado' => $calculo['total_devengado'],
            'total_deducciones_ajustado' => $calculo['total_deducciones'],
            'salario_neto_ajustado' => $calculo['salario_neto'],
        ]);

        return $preliquidacion->fresh(self::WITH);
    }
}
