<?php

namespace App\Services\Vsm;

use App\Models\Vsm\VsmConfiguracion;
use Illuminate\Support\Facades\DB;

class VsmConfiguracionService
{
    /**
     * Retorna el registro activo vigente con quien lo creó.
     */
    public function vigente(): ?VsmConfiguracion
    {
        return VsmConfiguracion::with('creadoPor:id,name')
            ->where('activo', true)
            ->latest()
            ->first();
    }

    /**
     * Historial completo de metas, de la más reciente a la más antigua.
     */
    public function historial(): \Illuminate\Database\Eloquent\Collection
    {
        return VsmConfiguracion::with('creadoPor:id,name')
            ->latest()
            ->get();
    }

    /**
     * Registra una nueva meta:
     * - Desactiva la anterior (si existe)
     * - Inserta la nueva como activa
     * - Todo en una transacción para garantir consistencia
     */
    public function crear(array $datos, int $usuarioId): VsmConfiguracion
    {
        return DB::transaction(function () use ($datos, $usuarioId) {
            VsmConfiguracion::where('activo', true)->update(['activo' => false]);

            return VsmConfiguracion::create([
                'meta_unidades_hora' => $datos['meta_unidades_hora'],
                'horas_semanales'    => $datos['horas_semanales'] ?? 44,
                'descripcion'        => $datos['descripcion'] ?? null,
                'activo'             => true,
                'creado_por'         => $usuarioId,
            ]);
        });
    }

    /**
     * Edita los datos de un registro existente (meta y/o descripción).
     * Si el registro editado está activo, invalida la caché de productividad.
     */
    public function actualizar(int $id, array $datos): VsmConfiguracion
    {
        $registro = VsmConfiguracion::findOrFail($id);

        $registro->update([
            'meta_unidades_hora' => $datos['meta_unidades_hora'],
            'horas_semanales'    => $datos['horas_semanales'] ?? $registro->horas_semanales,
            'descripcion'        => $datos['descripcion'] ?? $registro->descripcion,
        ]);

        return $registro->load('creadoPor:id,name');
    }

    /**
     * Elimina un registro.
     * No se permite eliminar el registro activo vigente.
     */
    public function eliminar(int $id): void
    {
        $registro = VsmConfiguracion::findOrFail($id);

        if ($registro->activo) {
            throw new \Exception('No se puede eliminar la meta vigente. Define una nueva meta primero.');
        }

        $registro->delete();
    }

    /**
     * Restaura un registro anterior como la meta activa.
     * Útil para revertir un cambio.
     */
    public function restaurar(int $id): VsmConfiguracion
    {
        return DB::transaction(function () use ($id) {
            $registro = VsmConfiguracion::findOrFail($id);

            VsmConfiguracion::where('activo', true)->update(['activo' => false]);

            $registro->activo = true;
            $registro->save();

            return $registro->load('creadoPor:id,name');
        });
    }
}
