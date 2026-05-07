<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Contratacion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ContratacionService
{


public function getAll(array $filters = []): LengthAwarePaginator
{
    $perPage = $filters['per_page'] ?? 10;

    return Contratacion::with([
            'tipoContrato:id,nombre,codigo',
            'usuario:id,name,email',
            'eps:id,nombre,nit',
            'arl:id,nombre,nit',
            'fondoPensiones:id,nombre,nit',
            'cajaPensiones:id,nombre,nit',
        ])
        ->when(!empty($filters['search']), function ($query) use ($filters) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                  ->orWhere('base_salario', 'like', "%{$search}%")
                  ->orWhere('no_salarial', 'like', "%{$search}%")

                  ->orWhereHas('usuario', function ($usuario) use ($search) {
                      $usuario->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                  })

                  ->orWhereHas('tipoContrato', function ($tipo) use ($search) {
                      $tipo->where('nombre', 'like', "%{$search}%")
                           ->orWhere('codigo', 'like', "%{$search}%");
                  })

                  ->orWhereHas('eps', function ($eps) use ($search) {
                      $eps->where('nombre', 'like', "%{$search}%");
                  });
            });
        })

        ->when(!empty($filters['fecha_inicio']), function ($query) use ($filters) {
            $query->whereDate('inicio_contratacion', '>=', $filters['fecha_inicio']);
        })

        ->when(!empty($filters['fecha_fin']), function ($query) use ($filters) {
            $query->whereDate('inicio_contratacion', '<=', $filters['fecha_fin']);
        })

        ->where('status', 1)
        ->orderByDesc('created_at')
        ->paginate($perPage);
}

public function getByUuid(string $uuid): Contratacion
{
    return Contratacion::with([
            'tipoContrato:id,nombre,codigo',
            'usuario:id,name,email',
            'eps:id,nombre,nit',
            'arl:id,nombre,nit',
            'fondoPensiones:id,nombre,nit',
            'cajaPensiones:id,nombre,nit',
        ])
        ->where('uuid', $uuid)
        ->firstOrFail();
}
    public function create(array $data): Contratacion
    {
        return Contratacion::create($data);
    }

 public function update(string $uuid, array $data): Contratacion
{
    $contratacion = Contratacion::where('uuid', $uuid)
        ->firstOrFail();

    $contratacion->update($data);

    return $contratacion->fresh([
        'tipoContrato:id,nombre,codigo',
        'usuario:id,name,email',
        'eps:id,nombre,nit',
        'arl:id,nombre,nit',
        'fondoPensiones:id,nombre,nit',
        'cajaPensiones:id,nombre,nit',
    ]);
}

public function delete(string $uuid): void
{
    $contratacion = Contratacion::where('uuid', $uuid)
        ->firstOrFail();

    $contratacion->delete();
}
}