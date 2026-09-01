<?php

namespace App\Services\Bsc;

use App\Models\RegistroIndicador;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Planes de acción y seguimiento (Etapa 5). No es una entidad aparte: son los
 * registros de indicadores que llevan un documento adjunto (el análisis / plan
 * que sube el responsable cuando carga el valor), igual que el "Descargar
 * análisis" del Dashboard de Indicadores. Aquí se listan y se descargan.
 */
class BscPlanService
{
    public function listar(?string $periodo = null, ?string $perspectiva = null): Collection
    {
        return RegistroIndicador::query()
            ->whereNotNull('documento')
            ->with('indicador.departamento')
            ->whereHas('indicador', function ($q) use ($perspectiva) {
                $q->whereNotNull('perspectiva');
                if ($perspectiva) {
                    $q->where('perspectiva', $perspectiva);
                }
            })
            ->when($periodo, function ($q) use ($periodo) {
                $q->where(function ($qq) use ($periodo) {
                    $qq->where('periodo', $periodo)
                        ->orWhereRaw('DATE_FORMAT(fecha, "%Y-%m") = ?', [$periodo]);
                });
            })
            ->orderByDesc('fecha')
            ->get()
            ->map(function (RegistroIndicador $r) {
                $ind = $r->indicador;
                $meta = (float) ($ind->meta ?? 0);
                $valor = (float) $r->valor;
                $eval = BscEstado::evaluar($valor, $meta, $ind->tipo_meta ?? null);

                return [
                    'id'             => $r->id,
                    'indicador'      => $ind->nombre ?? '—',
                    'perspectiva'    => $ind->perspectiva,
                    'objetivo'       => $ind->objetivo_estrategico,
                    'departamento'   => $ind->departamento->nombre ?? null,
                    'periodo'        => $r->periodo ?: ($r->fecha ? Carbon::parse($r->fecha)->format('Y-m') : null),
                    'fecha'          => $r->fecha,
                    'valor'          => $valor,
                    'meta'           => $meta,
                    'tipo_meta'      => $ind->tipo_meta,
                    'estado'         => $eval['estado'],
                    'observaciones'  => $r->observaciones,
                    'documento_url'  => $r->documento_url,
                    'descarga_url'   => url('/api/registro-indicadores/descargar/' . $r->id),
                ];
            });
    }
}
