<?php

namespace App\Services\Bsc;

use App\Models\Indicadores;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Consulta y clasificación BSC del catálogo de indicadores existente.
 */
class BscIndicadorService
{
    public function __construct(private BscCalculatorRegistry $registry)
    {
    }

    /** Indicadores para la pantalla de clasificación. */
    public function listar(?int $departamentoId = null, ?string $search = null): Collection
    {
        return Indicadores::query()
            ->with('departamento')
            ->when($departamentoId, fn ($q) => $q->where('departamento_id', $departamentoId))
            ->when($search, function ($q, $s) {
                $q->where(fn ($qq) => $qq->where('nombre', 'like', "%{$s}%")
                    ->orWhere('descripcion', 'like', "%{$s}%"));
            })
            ->orderBy('departamento_id')
            ->orderBy('orden')
            ->get()
            ->map(fn (Indicadores $i) => [
                'id'                   => $i->id,
                'nombre'               => $i->nombre,
                'descripcion'          => $i->descripcion,
                'formula'              => $i->formula,
                'meta'                 => $i->meta,
                'tipo_meta'            => $i->tipo_meta,
                'frecuencia'           => $i->frecuencia,
                'departamento'         => $i->departamento?->nombre,
                'departamento_id'      => $i->departamento_id,
                'perspectiva'          => $i->perspectiva,
                'objetivo_estrategico' => $i->objetivo_estrategico,
                'unidad'               => $i->unidad,
                'calculo_key'          => $i->calculo_key,
                'orden'                => $i->orden,
            ]);
    }

    /**
     * @param  array<string, mixed>  $datos  campos BSC ya validados
     */
    public function clasificar(Indicadores $indicador, array $datos): Indicadores
    {
        if (!empty($datos['calculo_key']) && !$this->registry->has($datos['calculo_key'])) {
            throw ValidationException::withMessages([
                'calculo_key' => 'calculo_key no reconocido.',
            ]);
        }

        $indicador->fill($datos)->save();

        return $indicador->fresh('departamento');
    }

    /** Objetivos estratégicos ya usados (autocompletar). */
    public function objetivosUsados(): Collection
    {
        return Indicadores::whereNotNull('objetivo_estrategico')
            ->distinct()
            ->orderBy('objetivo_estrategico')
            ->pluck('objetivo_estrategico');
    }

    public function calculatorsDisponibles(): array
    {
        return $this->registry->opciones();
    }
}
