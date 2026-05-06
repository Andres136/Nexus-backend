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

    public function getById(int $id): HorarioLaboral
    {
        return HorarioLaboral::findOrFail($id);
    }

    public function create(array $data): HorarioLaboral
    {
        return HorarioLaboral::create($data);
    }

    public function update(int $id, array $data): HorarioLaboral
    {
        $horario = HorarioLaboral::findOrFail($id);
        $horario->update($data);
        return $horario;
    }

    public function delete(int $id): void
    {
        $horario = HorarioLaboral::findOrFail($id);
        $horario->delete();
    }
}
