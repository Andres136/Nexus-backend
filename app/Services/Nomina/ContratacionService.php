<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Contratacion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContratacionService
{
    private const WITH = [
        'tipoContrato:id,nombre,codigo',
        'usuario:id,name,email',
        'empresa:id,nombre',
        'eps:id,nombre,nit',
        'arl:id,nombre,nit',
        'fondoPensiones:id,nombre,nit',
        'cajaPenciones:id,nombre,nit',
    ];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 10;

        return Contratacion::with(self::WITH)
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('uuid', 'like', "%{$search}%")
                      ->orWhere('base_salario', 'like', "%{$search}%")
                      ->orWhere('no_salarial', 'like', "%{$search}%")
                      ->orWhereHas('usuario', fn($u) => $u->where('name', 'like', "%{$search}%")
                                                           ->orWhere('email', 'like', "%{$search}%"))
                      ->orWhereHas('tipoContrato', fn($t) => $t->where('nombre', 'like', "%{$search}%")
                                                               ->orWhere('codigo', 'like', "%{$search}%"))
                      ->orWhereHas('eps', fn($e) => $e->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->when(!empty($filters['fecha_inicio']), fn($q) =>
                $q->whereDate('inicio_contratacion', '>=', $filters['fecha_inicio']))
            ->when(!empty($filters['fecha_fin']), fn($q) =>
                $q->whereDate('inicio_contratacion', '<=', $filters['fecha_fin']))
            ->when(isset($filters['status']), fn($q) =>
                $q->where('status', $filters['status']))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): Contratacion
    {
        return Contratacion::with(self::WITH)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function create(array $data): Contratacion
    {
        return DB::transaction(function () use ($data) {
            $contratacion = Contratacion::create($data);

            Log::info('Contratación creada', ['uuid' => $contratacion->uuid, 'users_id' => $contratacion->users_id]);

            return $contratacion->load(self::WITH);
        });
    }

    public function update(string $uuid, array $data): Contratacion
    {
        return DB::transaction(function () use ($uuid, $data) {
            $contratacion = Contratacion::where('uuid', $uuid)->firstOrFail();

            $contratacion->update($data);

            Log::info('Contratación actualizada', ['uuid' => $contratacion->uuid]);

            return $contratacion->fresh(self::WITH);
        });
    }

    public function delete(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $contratacion = Contratacion::where('uuid', $uuid)->firstOrFail();

            $contratacion->delete();

            Log::info('Contratación eliminada', ['uuid' => $contratacion->uuid]);
        });
    }
}
