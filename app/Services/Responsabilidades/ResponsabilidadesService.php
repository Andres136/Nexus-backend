<?php

namespace App\Services\Responsabilidades;

use App\Models\Traslados\Responsabilidad;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ResponsabilidadesService
{
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        $query = Responsabilidad::query();

        // 🔍 Filtro por nombre
        if (!empty($filtros['search'])) {
            $query->where('nombre', 'like', '%' . $filtros['search'] . '%');
        }

     

        // ↕️ Ordenamiento
        $orderBy = $filtros['order_by'] ?? 'nombre';
        $order   = $filtros['order'] ?? 'asc';

        $query->orderBy($orderBy, $order);

        // 📄 Paginación
        $perPage = $filtros['per_page'] ?? 10;

        return $query->paginate($perPage);
    }

    public function crear(array $data): Responsabilidad
    {
        return Responsabilidad::create($data);
    }


    //Update
public function actualizar(string $id, array $data): ?Responsabilidad
{
    $responsabilidad = Responsabilidad::find($id);

    if (!$responsabilidad) {
        return null;
    }

    $responsabilidad->update($data);

    return $responsabilidad;
}


    //Eliminar+
    public function eliminar(string $id): bool
    {
        $responsabilidad = Responsabilidad::find($id);
        if (!$responsabilidad) {
            return false;
        }
        return $responsabilidad->delete();
    }
}