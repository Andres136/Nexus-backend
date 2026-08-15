<?php

namespace App\Services\Hseq;

use App\Models\Hseq\EficaciaEvaluacion;
use App\Models\RegistroDiario\Novedades;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EficaciaEvaluacionService
{
    public function listarPorNovedad($novedadId)
    {
        return EficaciaEvaluacion::where('novedad_id', $novedadId)
            ->with(['verificador', 'calificaciones.hallazgo'])
            ->latest()
            ->get();
    }

    public function registrar($novedadId, array $data, $verificadorId): EficaciaEvaluacion
    {
        $novedad = Novedades::with('hallazgos')->findOrFail($novedadId);

        if ($novedad->estado !== 'CERRADA') {
            throw ValidationException::withMessages([
                'estado' => 'Solo se puede evaluar la eficacia de una no conformidad que ya esté cerrada.',
            ]);
        }

        $hallazgosValidos = $novedad->hallazgos->pluck('id')->all();

        $calificacionesValidas = collect($data['calificaciones'])
            ->filter(fn ($c) => in_array((int) $c['hallazgo_id'], $hallazgosValidos, true));

        if ($calificacionesValidas->isEmpty()) {
            throw ValidationException::withMessages([
                'calificaciones' => 'Califica al menos un hallazgo vinculado a esta novedad.',
            ]);
        }

        $resultado = $this->calcularResultado($calificacionesValidas->pluck('calificacion'));

        return DB::transaction(function () use ($novedad, $data, $verificadorId, $calificacionesValidas, $resultado) {
            $evaluacion = EficaciaEvaluacion::create([
                'novedad_id' => $novedad->id,
                'verificador_id' => $verificadorId,
                'resultado' => $resultado,
                'observacion' => $data['observacion'],
                'proxima_verificacion' => $data['proxima_verificacion'] ?? null,
            ]);

            foreach ($calificacionesValidas as $calificacion) {
                $evaluacion->calificaciones()->create([
                    'hallazgo_id' => $calificacion['hallazgo_id'],
                    'calificacion' => $calificacion['calificacion'],
                ]);
            }

            return $evaluacion->fresh(['verificador', 'calificaciones.hallazgo']);
        });
    }

    /**
     * Resultado final de la Novedad a partir del promedio de las
     * calificaciones (1-5) de cada hallazgo: 1-2 → no eficaz, 3 → parcial,
     * 4-5 → eficaz.
     */
    private function calcularResultado($calificaciones): string
    {
        $promedio = (int) round($calificaciones->avg());

        return match (true) {
            $promedio <= 2 => 'no_eficaz',
            $promedio === 3 => 'parcial',
            default => 'eficaz',
        };
    }
}
