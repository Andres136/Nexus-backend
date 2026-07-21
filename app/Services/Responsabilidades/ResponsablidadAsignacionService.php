<?php
namespace App\Services\Responsabilidades;
use App\Models\Traslados\Responsabilidad;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
   if ($responsabilidad->usuarios()->where('user_id', $data['user_id'])->exists()) {
    $responsabilidad->usuarios()->updateExistingPivot(
        $data['user_id'],
        [
            'bodega_id' => $data['bodega_id'] ?? null,
            'sede_id' => $data['sede_id'] ?? null,
            'activo' => $data['activo'] ?? true,
            'fecha_asignacion' => $data['fecha_asignacion'] ?? now(),
            'fecha_fin' => $data['fecha_fin'] ?? null,
        ]
    );
} else {
    $responsabilidad->usuarios()->attach(
        $data['user_id'],
        [
            'bodega_id' => $data['bodega_id'] ?? null,
            'sede_id' => $data['sede_id'] ?? null,
            'activo' => $data['activo'] ?? true,
            'fecha_asignacion' => $data['fecha_asignacion'] ?? now(),
            'fecha_fin' => $data['fecha_fin'] ?? null,
        ]
    );
}


            return $responsabilidad->load('usuarios');
        });
    }


    /**
     * Listar las responsabilidades asignadas
     */


public function listarResponsabilidades(
    int $perPage = 15,
    array $filtros = []
): LengthAwarePaginator {

    return Responsabilidad::query()
        ->whereHas('usuarios', function ($q) use ($filtros) {
            if (!empty($filtros['sede_id'])) {
                $q->where('responsabilidades_user.sede_id', (int) $filtros['sede_id']);
            }

            if (!empty($filtros['bodega_id'])) {
                $q->where('responsabilidades_user.bodega_id', (int) $filtros['bodega_id']);
            }

            $q->where(function ($q) {
                $q->whereNull('responsabilidades_user.fecha_fin')
                  ->orWhere('responsabilidades_user.fecha_fin', '>=', now()->toDateString());
            });
        })
        ->with([
            'usuarios' => function ($q) use ($filtros) {
                // ✅ Agregar JOIN para traer nombres directamente
                // Los campos de responsabilidades_user se alían como pivot_* para que
                // Eloquent los hidrate en $usuario->pivot (si no, el select() pisa por
                // completo el select por defecto de withPivot() y el pivot llega vacío,
                // y además 'id' colisiona con users.id).
                $q->leftJoin('sedes', 'responsabilidades_user.sede_id', '=', 'sedes.id')
                  ->leftJoin('bodegas', 'responsabilidades_user.bodega_id', '=', 'bodegas.id')
                  ->select([
                      'users.*',
                      'responsabilidades_user.responsabilidad_id as pivot_responsabilidad_id',
                      'responsabilidades_user.user_id as pivot_user_id',
                      'responsabilidades_user.id as pivot_id',
                      'responsabilidades_user.bodega_id as pivot_bodega_id',
                      'responsabilidades_user.sede_id as pivot_sede_id',
                      'responsabilidades_user.activo as pivot_activo',
                      'responsabilidades_user.fecha_asignacion as pivot_fecha_asignacion',
                      'responsabilidades_user.fecha_fin as pivot_fecha_fin',
                      'responsabilidades_user.created_at as pivot_created_at',
                      'responsabilidades_user.updated_at as pivot_updated_at',
                      'sedes.nombre as sede_nombre',
                      'bodegas.nombre as bodega_nombre'
                  ]);

                if (!empty($filtros['sede_id'])) {
                    $q->wherePivot('sede_id', (int) $filtros['sede_id']);
                }

                if (!empty($filtros['bodega_id'])) {
                    $q->wherePivot('bodega_id', (int) $filtros['bodega_id']);
                }

                $q->where(function ($q) {
                    $q->whereNull('responsabilidades_user.fecha_fin')
                      ->orWhere('responsabilidades_user.fecha_fin', '>=', now()->toDateString());
                });
            }
        ])
        ->orderByDesc('id')
        ->paginate($perPage);
}




//DESACTIVAR RESPONSABILIDAD en user

public function desactivarResponsabilidad(
    int $responsabilidadId,
    int $userId
): bool {
    return DB::transaction(function () use ($responsabilidadId, $userId) {

        $responsabilidad = Responsabilidad::findOrFail($responsabilidadId);
        $responsabilidad->usuarios()->updateExistingPivot($userId, [
            'activo' => false,
            'fecha_fin' => now()
        ]);

        return true;
    });
}

}