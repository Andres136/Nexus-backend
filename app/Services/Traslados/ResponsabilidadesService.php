<?php

namespace App\Services\Traslados;

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

        // 🧩 Filtro por estado (si aplica)
        if (isset($filtros['activo'])) {
            $query->where('activo', $filtros['activo']);
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
}