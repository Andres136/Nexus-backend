<?php

namespace App\Services\Bsc;

use App\Models\Bsc\BscEtapa;
use Illuminate\Support\Carbon;

class BscEtapaService
{
    /**
     * Las 5 etapas del año pedido (por defecto el actual). Si aún no existen
     * para ese año, se crean en estado pendiente a partir de la plantilla.
     */
    public function listar(?int $anio = null): array
    {
        $actual = (int) now()->year;
        $anio ??= $actual;

        // Solo se auto-crea el año en curso o el siguiente (planeación
        // anticipada). Años pasados sin datos quedan vacíos; años lejanos se
        // ignoran para no generar basura.
        if ($anio >= 2020 && $anio <= $actual + 1) {
            $this->asegurarAnio($anio);
        }

        $etapas = BscEtapa::with('responsable:id,name')
            ->delAnio($anio)
            ->orderBy('numero')
            ->get();

        $completadas = $etapas->where('estado', 'completada')->count();
        $anios = BscEtapa::distinct()->orderByDesc('anio')->pluck('anio');

        return [
            'anio'   => $anio,
            'anios'  => $anios,
            'etapas' => $etapas,
            'resumen' => [
                'total'       => $etapas->count(),
                'completadas' => $completadas,
                'en_progreso' => $etapas->where('estado', 'en_progreso')->count(),
                'avance_pct'  => $etapas->count() ? round($completadas / $etapas->count() * 100) : 0,
            ],
        ];
    }

    /** @param array<string, mixed> $datos ya validados */
    public function actualizar(BscEtapa $etapa, array $datos): BscEtapa
    {
        if (($datos['estado'] ?? $etapa->estado) === 'completada' && !$etapa->fecha_completada) {
            $datos['fecha_completada'] = Carbon::now()->toDateString();
        }
        if (($datos['estado'] ?? null) && $datos['estado'] !== 'completada') {
            $datos['fecha_completada'] = null;
        }

        $etapa->fill($datos)->save();

        return $etapa->fresh('responsable:id,name');
    }

    private function asegurarAnio(int $anio): void
    {
        if (BscEtapa::delAnio($anio)->exists()) {
            return;
        }

        foreach (BscEtapa::PLANTILLA as [$numero, $nombre, $descripcion]) {
            BscEtapa::create([
                'numero'      => $numero,
                'anio'        => $anio,
                'nombre'      => $nombre,
                'descripcion' => $descripcion,
                'estado'      => 'pendiente',
            ]);
        }
    }
}
