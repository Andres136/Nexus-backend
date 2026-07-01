<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Nomina;
use App\Models\Nomina\NominaConceptoContable;
use Illuminate\Support\Collection;

class NominaPucPayloadService
{
    public function generar(string $uuid): array
    {
        $nomina = Nomina::with([
            'empleado:id,name,email',
            'contratacion.empresa',
            'contratacion.tipoContrato',
        ])->where('uuid', $uuid)->firstOrFail();

        if (! $nomina->liquidada) {
            throw new \LogicException('Solo se puede generar payload contable para nóminas liquidadas.');
        }

        if (in_array($nomina->estado_contable, ['anulada', 'reversada'], true)) {
            throw new \LogicException('No se puede generar un payload contable normal para una nómina anulada o reversada.');
        }

        $conceptos = NominaConceptoContable::with('puck')
            ->where('activo', true)
            ->get()
            ->keyBy('codigo');

        $faltantes = [];
        $devengados = [];
        $deducciones = [];
        $aportesEmpleador = [];
        $neto = null;

        $novedades = collect($nomina->detalle_novedades_retroactivas ?? []);
        $novedadesDevengo = $this->sumarNovedades($novedades, 'devengo');
        $novedadesDeduccion = $this->sumarNovedades($novedades, 'deduccion');
        $descuentosAdicionales = max(0, (float) $nomina->total_descuentos_adicionales - $novedadesDeduccion);

        $this->agregarLinea($devengados, $faltantes, $conceptos, 'salario_base', (float) $nomina->salario_base_devengado, $nomina);
        $this->agregarLinea($devengados, $faltantes, $conceptos, 'auxilio_transporte', (float) $nomina->auxilio_transporte, $nomina);
        $this->agregarLinea($devengados, $faltantes, $conceptos, 'pago_no_salarial', (float) $nomina->pago_no_prestacional, $nomina);
        $this->agregarLinea($devengados, $faltantes, $conceptos, 'horas_extras_diurnas', (float) $nomina->valor_horas_extras_diurnas, $nomina);
        $this->agregarLinea($devengados, $faltantes, $conceptos, 'horas_extras_nocturnas', (float) $nomina->valor_horas_extras_nocturnas, $nomina);
        $this->agregarLinea($devengados, $faltantes, $conceptos, 'horas_festivas', (float) $nomina->valor_horas_festivas, $nomina);
        $this->agregarLinea($devengados, $faltantes, $conceptos, 'horas_nocturnas_festivas', (float) $nomina->valor_horas_nocturnas_festivas, $nomina);
        $this->agregarLinea($devengados, $faltantes, $conceptos, 'comisiones', (float) $nomina->total_comisiones, $nomina);
        $this->agregarLinea($devengados, $faltantes, $conceptos, 'novedades_retroactivas_devengo', $novedadesDevengo, $nomina);

        $this->agregarLinea($deducciones, $faltantes, $conceptos, 'salud_empleado', (float) $nomina->deduccion_salud, $nomina);
        $this->agregarLinea($deducciones, $faltantes, $conceptos, 'pension_empleado', (float) $nomina->deduccion_pension, $nomina);
        $this->agregarLinea($deducciones, $faltantes, $conceptos, 'descuentos_adicionales', $descuentosAdicionales, $nomina);
        $this->agregarLinea($deducciones, $faltantes, $conceptos, 'novedades_retroactivas_deduccion', $novedadesDeduccion, $nomina);

        $this->agregarLinea($neto, $faltantes, $conceptos, 'neto_pagar', (float) $nomina->salario_neto, $nomina, true);

        $this->agregarPartidaDobleAporte($aportesEmpleador, $faltantes, $conceptos, 'salud_empleador_gasto', 'salud_empleador_pagar', (float) $nomina->costo_salud_empleador, $nomina);
        $this->agregarPartidaDobleAporte($aportesEmpleador, $faltantes, $conceptos, 'pension_empleador_gasto', 'pension_empleador_pagar', (float) $nomina->costo_pension_empleador, $nomina);
        $this->agregarPartidaDobleAporte($aportesEmpleador, $faltantes, $conceptos, 'arl_gasto', 'arl_pagar', (float) $nomina->costo_arl, $nomina);
        $this->agregarPartidaDobleAporte($aportesEmpleador, $faltantes, $conceptos, 'sena_gasto', 'sena_pagar', (float) $nomina->costo_sena, $nomina);
        $this->agregarPartidaDobleAporte($aportesEmpleador, $faltantes, $conceptos, 'icbf_gasto', 'icbf_pagar', (float) $nomina->costo_icbf, $nomina);
        $this->agregarPartidaDobleAporte($aportesEmpleador, $faltantes, $conceptos, 'caja_compensacion_gasto', 'caja_compensacion_pagar', (float) $nomina->costo_caja_compensacion, $nomina);

        $lineas = collect($devengados)
            ->merge($deducciones)
            ->when($neto, fn ($items) => $items->push($neto))
            ->merge($aportesEmpleador);

        $totalDebitos = $this->sumarPorNaturaleza($lineas, 'debito');
        $totalCreditos = $this->sumarPorNaturaleza($lineas, 'credito');

        $cuadrado = empty($faltantes) && abs($totalDebitos - $totalCreditos) < 0.01;

        return [
            'valido' => empty($faltantes),
            'cuentas_faltantes' => array_values($faltantes),
            'documento_nomina' => [
                'tipo' => 'nomina_individual',
                'uuid' => $nomina->uuid,
                'periodo_inicio' => $nomina->periodo_inicio?->toDateString(),
                'periodo_fin' => $nomina->periodo_fin?->toDateString(),
                'fecha_liquidacion' => $nomina->fecha_liquidacion?->toDateTimeString(),
            ],
            'empleado' => [
                'id' => $nomina->empleado?->id,
                'nombre' => $nomina->empleado?->name,
                'correo' => $nomina->empleado?->email,
                'tipo_documento' => $nomina->contratacion?->tipo_documento,
                'numero_documento' => $nomina->contratacion?->numero_documento,
                'cargo' => $nomina->contratacion?->cargo,
            ],
            'empresa' => [
                'id' => $nomina->contratacion?->empresa?->id,
                'nombre' => $nomina->contratacion?->empresa?->nombre,
            ],
            'contabilidad' => [
                'devengados' => $devengados,
                'deducciones' => $deducciones,
                'aportes_empleador' => $aportesEmpleador,
                'neto' => $neto,
                'asientos' => $lineas->values()->all(),
                'totales' => [
                    'total_devengado' => round((float) $nomina->total_devengado, 2),
                    'total_deducciones' => round((float) $nomina->total_deducciones, 2),
                    'neto_pagar' => round((float) $nomina->salario_neto, 2),
                    'base_aportes_empleador' => round((float) $nomina->base_aportes_empleador, 2),
                    'costo_total_empleador' => round((float) $nomina->costo_total_empleador, 2),
                    'total_debitos' => $totalDebitos,
                    'total_creditos' => $totalCreditos,
                    'diferencia' => round($totalDebitos - $totalCreditos, 2),
                    'cuadrado' => $cuadrado,
                ],
            ],
        ];
    }

    private function agregarPartidaDobleAporte(
        array &$bucket,
        array &$faltantes,
        Collection $conceptos,
        string $codigoGasto,
        string $codigoPagar,
        float $valor,
        Nomina $nomina,
    ): void {
        $this->agregarLinea($bucket, $faltantes, $conceptos, $codigoGasto, $valor, $nomina);
        $this->agregarLinea($bucket, $faltantes, $conceptos, $codigoPagar, $valor, $nomina);
    }

    private function agregarLinea(
        array|null &$bucket,
        array &$faltantes,
        Collection $conceptos,
        string $codigo,
        float $valor,
        Nomina $nomina,
        bool $single = false,
    ): void {
        $valor = round($valor, 2);

        if ($valor <= 0) {
            return;
        }

        $concepto = $conceptos->get($codigo);

        if (! $concepto || ! $concepto->puck || ! $concepto->puck->activo) {
            $faltantes[$codigo] = [
                'codigo' => $codigo,
                'valor' => $valor,
                'motivo' => ! $concepto ? 'concepto_no_configurado' : 'cuenta_puc_no_configurada',
            ];

            return;
        }

        $linea = [
            'codigo' => $concepto->codigo,
            'concepto' => $concepto->nombre,
            'tipo' => $concepto->tipo,
            'valor' => $valor,
            'naturaleza' => $concepto->naturaleza,
            'cuenta_puc' => [
                'id' => $concepto->puck->id,
                'numero' => $concepto->puck->numero,
                'nombre' => $concepto->puck->nombre,
                'naturaleza' => $concepto->puck->naturaleza,
            ],
            'tercero' => $concepto->requiere_tercero ? [
                'id' => $nomina->empleado?->id,
                'nombre' => $nomina->empleado?->name,
                'tipo_documento' => $nomina->contratacion?->tipo_documento,
                'numero_documento' => $nomina->contratacion?->numero_documento,
            ] : null,
            'centro_costo' => null,
        ];

        if ($single) {
            $bucket = $linea;

            return;
        }

        $bucket[] = $linea;
    }

    private function sumarNovedades(Collection $novedades, string $tipo): float
    {
        return round((float) $novedades
            ->where('tipo', $tipo)
            ->sum(fn ($item) => (float) ($item['valor'] ?? 0)), 2);
    }

    private function sumarPorNaturaleza(Collection $lineas, string $naturaleza): float
    {
        return round((float) $lineas
            ->where('naturaleza', $naturaleza)
            ->sum('valor'), 2);
    }
}
