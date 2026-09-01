<?php

namespace App\Services\Bsc;

use App\Models\Bsc\BscPerspectiva;
use App\Models\Indicadores;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Arma el Cuadro de Mando Integral agrupado por perspectiva para un periodo:
 * valor vs meta, semáforo, % de cumplimiento e histórico para el sparkline.
 */
class BscDashboardService
{
    /**
     * @return array{periodo: string, perspectivas: Collection}
     */
    public function construir(string $periodo, int $nHistorial = 6): array
    {
        $desdeCarbon = Carbon::createFromFormat('Y-m-d', $periodo . '-01')
            ->startOfMonth()->subMonths(max(0, $nHistorial - 1));
        $desde = $desdeCarbon->format('Y-m');
        $hasta = Carbon::createFromFormat('Y-m-d', $periodo . '-01')->endOfMonth();

        $perspectivas = BscPerspectiva::orderBy('orden')->get();

        // El BSC es una vista integral de toda la empresa: sin filtro por
        // departamento (el departamento se asigna a la perspectiva, no filtra
        // el tablero). Se recorre el catálogo una sola vez y se agrupa.
        $porPerspectiva = $this->cargarIndicadores($periodo, $desde, $desdeCarbon, $hasta)
            ->groupBy('perspectiva');

        $data = $perspectivas->map(function (BscPerspectiva $persp) use ($porPerspectiva, $periodo) {
            $items = $porPerspectiva->get($persp->clave, collect())
                ->map(fn (Indicadores $ind) => $this->serializarIndicador($ind, $periodo))
                ->values();

            $conCumplimiento = $items->where('tiene_dato', true)->whereNotNull('cumplimiento_pct');

            return [
                'clave'       => $persp->clave,
                'nombre'      => $persp->nombre,
                'color'       => $persp->color,
                'icono_url'   => $persp->icono_url,
                'gauge_pct'   => $conCumplimiento->count()
                    ? round($conCumplimiento->avg('cumplimiento_pct'), 1)
                    : null,
                'indicadores' => $items,
            ];
        });

        return ['periodo' => $periodo, 'perspectivas' => $data];
    }

    private function cargarIndicadores(
        string $periodo,
        string $desde,
        Carbon $desdeCarbon,
        Carbon $hasta
    ): Collection {
        return Indicadores::query()
            ->with(['departamento', 'registros' => function ($q) use ($desde, $periodo, $desdeCarbon, $hasta) {
                // Registros automáticos por `periodo`; los manuales viejos
                // (sin periodo) por su `fecha` dentro de la ventana.
                $q->where(function ($qq) use ($desde, $periodo, $desdeCarbon, $hasta) {
                    $qq->whereBetween('periodo', [$desde, $periodo])
                        ->orWhere(function ($q3) use ($desdeCarbon, $hasta) {
                            $q3->whereNull('periodo')
                                ->whereBetween('fecha', [$desdeCarbon->toDateString(), $hasta->toDateString()]);
                        });
                })->orderBy('fecha');
            }])
            ->clasificados()
            ->orderBy('orden')
            ->get();
    }

    private function serializarIndicador(Indicadores $ind, string $periodo): array
    {
        $registros = $ind->registros;

        $delPeriodo = $registros->firstWhere('periodo', $periodo)
            ?? $registros->first(fn ($r) => $r->fecha && Carbon::parse($r->fecha)->format('Y-m') === $periodo);

        $meta = (float) ($ind->meta ?? 0);
        $valor = $delPeriodo ? (float) $delPeriodo->valor : null;
        $eval = BscEstado::evaluar($valor, $meta, $ind->tipo_meta);

        $historial = $registros
            ->map(function ($r) {
                $p = $r->periodo ?: ($r->fecha ? Carbon::parse($r->fecha)->format('Y-m') : null);

                return $p ? ['periodo' => $p, 'valor' => (float) $r->valor] : null;
            })
            ->filter()
            ->unique('periodo')
            ->sortBy('periodo')
            ->values();

        return [
            'id'                   => $ind->id,
            'nombre'               => $ind->nombre,
            'objetivo_estrategico' => $ind->objetivo_estrategico,
            'formula'              => $ind->formula,
            'unidad'               => $ind->unidad,
            'frecuencia'           => $ind->frecuencia,
            'meta'                 => $meta,
            'tipo_meta'            => $ind->tipo_meta,
            'origen'               => $ind->calculo_key ? 'automatico' : 'manual',
            'calculo_key'          => $ind->calculo_key,
            'departamento'         => $ind->departamento?->nombre,
            'valor'                => $valor,
            'tiene_dato'           => $valor !== null,
            'estado'               => $eval['estado'],
            'cumplimiento_pct'     => $eval['estado'] === 'sin datos' ? null : $eval['cumplimiento_pct'],
            'fecha_registro'       => $delPeriodo?->fecha,
            'observaciones'        => $delPeriodo?->observaciones,
            'numerador'            => $delPeriodo?->numerador,
            'denominador'          => $delPeriodo?->denominador,
            'historial'            => $historial,
        ];
    }
}
