<?php

namespace App\Services\Bsc;

/**
 * Semáforo y % de cumplimiento de un indicador contra su meta.
 * Misma escala que RegistroIndicadoresController::procesarRegistro
 * (OK / Medio / Crítico con umbral del 80%).
 */
class BscEstado
{
    /**
     * @return array{estado: string, cumplimiento_pct: float}
     */
    public static function evaluar(?float $valor, float $meta, ?string $tipoMeta): array
    {
        if ($valor === null || $meta <= 0) {
            return ['estado' => 'sin datos', 'cumplimiento_pct' => 0.0];
        }

        $tipo = strtolower(trim($tipoMeta ?? 'mayor'));
        $menor = str_contains($tipo, 'menor') || str_contains($tipo, '≤');

        if ($menor) {
            $cumplimiento = $valor > 0 ? round(($meta / $valor) * 100, 2) : 100.0;
            $estado = $valor <= $meta ? 'ok' : ($valor <= $meta * 1.2 ? 'medio' : 'critico');
        } else {
            $cumplimiento = round(($valor / $meta) * 100, 2);
            $estado = $valor >= $meta ? 'ok' : ($valor >= $meta * 0.8 ? 'medio' : 'critico');
        }

        return [
            'estado' => $estado,
            'cumplimiento_pct' => max(0.0, min($cumplimiento, 200.0)),
        ];
    }
}
