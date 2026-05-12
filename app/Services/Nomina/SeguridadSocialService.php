<?php

namespace App\Services\Nomina;

use App\Models\Nomina\SeguridadSocial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SeguridadSocialService
{

public function create(array $data): SeguridadSocial
{
    return SeguridadSocial::create($data);
}
 public function getAll(array $filters = []): LengthAwarePaginator
{
    $perPage = $filters['per_page'] ?? 10;

    return SeguridadSocial::query()
        ->withTrashed()

        ->when(!empty($filters['search']), function ($query) use ($filters) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('nit', 'like', "%{$search}%")
                  ->orWhere('direccion', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        })

        ->when(!empty($filters['fecha_inicio']), function ($query) use ($filters) {
            $query->whereDate('fecha_inicio', '>=', $filters['fecha_inicio']);
        })

        ->when(!empty($filters['fecha_fin']), function ($query) use ($filters) {
            $query->whereDate('fecha_inicio', '<=', $filters['fecha_fin']);
        })

        ->orderByDesc('created_at')
        ->paginate($perPage);
}



public function getById(string $uuid): SeguridadSocial
{
    return SeguridadSocial::withTrashed()
        ->where('uuid', $uuid)
        ->firstOrFail();
}

public function update(string $uuid, array $data): SeguridadSocial
{
    $seguridadSocial = SeguridadSocial::withTrashed()
        ->where('uuid', $uuid)
        ->firstOrFail();

    $seguridadSocial->update($data);

    return $seguridadSocial->fresh();
}

    public function delete(string $uuid): void
    {
        $seguridadSocial = SeguridadSocial::where('uuid', $uuid)->firstOrFail();
        $seguridadSocial->delete();
    }
}