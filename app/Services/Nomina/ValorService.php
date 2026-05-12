<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Valor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValorService
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 10;

        return Valor::query()
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): Valor
    {
        return Valor::where('uuid', $uuid)->firstOrFail();
    }

    public function store(array $data): Valor
    {
        return DB::transaction(function () use ($data) {
            $valor = Valor::create($data);

            Log::info('Valores de hora creados', [
                'uuid'              => $valor->uuid,
                'valor_hora_normal' => $valor->valor_hora_normal,
            ]);

            return $valor;
        });
    }

    public function update(string $uuid, array $data): Valor
    {
        return DB::transaction(function () use ($uuid, $data) {
            $valor = $this->getByUuid($uuid);

            $valor->update($data);

            Log::info('Valores de hora actualizados', ['uuid' => $valor->uuid]);

            return $valor->fresh();
        });
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $valor = $this->getByUuid($uuid);

            $valor->delete();

            Log::info('Valores de hora eliminados', ['uuid' => $valor->uuid]);
        });
    }
}
