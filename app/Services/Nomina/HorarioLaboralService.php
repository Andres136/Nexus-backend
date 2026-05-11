<?php

namespace App\Services\Nomina;

use App\Models\Nomina\HorarioLaboral;
use Illuminate\Support\Collection;

class HorarioLaboralService
{
    public function getAll(): Collection
    {
        return HorarioLaboral::all();
    }

    public function getByUuid(string $uuid): HorarioLaboral  // ← getById(int $id) → getByUuid(string $uuid)
    {
        return HorarioLaboral::where('uuid', $uuid)  
            ->firstOrFail();
    }

    public function create(array $data): HorarioLaboral
    {
        return HorarioLaboral::create($data);
    }

    public function update(string $uuid, array $data): HorarioLaboral  
    {
        $horario = HorarioLaboral::where('uuid', $uuid)  
            ->firstOrFail();

        $horario->update($data);

        return $horario->fresh();  
    }

    public function delete(string $uuid): void  
    {
        $horario = HorarioLaboral::where('uuid', $uuid)  
            ->firstOrFail();

        $horario->delete();
    }
}