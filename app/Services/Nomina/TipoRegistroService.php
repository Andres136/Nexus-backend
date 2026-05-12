<?php

namespace App\Services\Nomina;

use App\Models\Nomina\TipoRegistro;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TipoRegistroService
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 10;

        return TipoRegistro::query()
            ->when(!empty($filters['search']), fn($q) =>
                $q->where('name', 'like', "%{$filters['search']}%"))
            ->when(isset($filters['activo']), fn($q) =>
                $q->where('activo', $filters['activo']))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getAllActivos(): Collection
    {
        return TipoRegistro::where('activo', true)->orderBy('name')->get();
    }

    public function getByUuid(string $uuid): TipoRegistro
    {
        return TipoRegistro::where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): TipoRegistro
    {
        return DB::transaction(function () use ($data) {
            $tipo = TipoRegistro::create($data);

            Log::info('Tipo de registro creado', ['uuid' => $tipo->uuid, 'name' => $tipo->name]);

            return $tipo;
        });
    }

    public function update(string $uuid, array $data): TipoRegistro
    {
        return DB::transaction(function () use ($uuid, $data) {
            $tipo = TipoRegistro::where('uuid', $uuid)->firstOrFail();

            $tipo->update($data);

            Log::info('Tipo de registro actualizado', ['uuid' => $tipo->uuid]);

            return $tipo->fresh();
        });
    }

    public function delete(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $tipo = TipoRegistro::where('uuid', $uuid)->firstOrFail();

            $tipo->delete();

            Log::info('Tipo de registro eliminado', ['uuid' => $tipo->uuid]);
        });
    }
}
