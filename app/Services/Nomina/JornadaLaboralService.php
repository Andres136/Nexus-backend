<?php

namespace App\Services\Nomina;

use App\Models\Nomina\JornadaLaboral;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JornadaLaboralService
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 10;

        return JornadaLaboral::query()
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('horas_semanales', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): JornadaLaboral
    {
        return JornadaLaboral::where('uuid', $uuid)->firstOrFail();
    }

    public function store(array $data): JornadaLaboral
    {
        return DB::transaction(function () use ($data) {
            $jornada = JornadaLaboral::create($data);

            Log::info('Jornada laboral creada', [
                'uuid'            => $jornada->uuid,
                'nombre'          => $jornada->nombre,
                'horas_semanales' => $jornada->horas_semanales,
            ]);

            return $jornada;
        });
    }

    public function update(string $uuid, array $data): JornadaLaboral
    {
        return DB::transaction(function () use ($uuid, $data) {
            $jornada = $this->getByUuid($uuid);

            $jornada->update($data);

            Log::info('Jornada laboral actualizada', ['uuid' => $jornada->uuid]);

            return $jornada->fresh();
        });
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $jornada = $this->getByUuid($uuid);

            $jornada->delete();

            Log::info('Jornada laboral eliminada', ['uuid' => $jornada->uuid]);
        });
    }
}
