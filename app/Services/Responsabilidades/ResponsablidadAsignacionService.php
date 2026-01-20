<?php
namespace App\Services\Responsabilidades;
use App\Models\Traslados\Responsabilidad;
use Illuminate\Support\Facades\DB;

class ResponsablidadAsignacionService
{
    /**
     * Asignar una responsabilidad a un usuario.
     */
   public function asignar(
        int $responsabilidadId,
        array $data
    ): Responsabilidad {
        return DB::transaction(function () use ($responsabilidadId, $data) {

            $responsabilidad = Responsabilidad::findOrFail($responsabilidadId);
            $responsabilidad->usuarios()->syncWithoutDetaching([
                $data['user_id'] => [
                    'bodega_id'        => $data['bodega_id'] ?? null,
                    'sede_id'          => $data['sede_id'] ?? null,
                    'activo'           => $data['activo'] ?? true,
                    'fecha_asignacion' => $data['fecha_asignacion'] ?? now(),
                    'fecha_fin'        => $data['fecha_fin'] ?? null,
                ]
            ]);

            return $responsabilidad->load('usuarios');
        });
    }
}