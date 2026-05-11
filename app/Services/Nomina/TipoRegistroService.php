<?php

namespace App\Services\Nomina;

use App\Models\Nomina\TipoRegistro;
use Illuminate\Support\Collection;

class TipoRegistroService
{
    public function getAll(): Collection
    {
        return TipoRegistro::where('activo', true)->get();
    }

    public function getByUuid(string $uuid): TipoRegistro         // ← getById(int $id) → getByUuid(string $uuid)
    {
        return TipoRegistro::where('uuid', $uuid)                 // ← findOrFail($id) → where + firstOrFail
            ->firstOrFail();
    }

    public function create(array $data): TipoRegistro
    {
        return TipoRegistro::create($data);
    }

    public function update(string $uuid, array $data): TipoRegistro  // ← int $id → string $uuid
    {
        $tipoRegistro = TipoRegistro::where('uuid', $uuid)        // ← findOrFail($id) → where + firstOrFail
            ->firstOrFail();

        $tipoRegistro->update($data);

        return $tipoRegistro->fresh();                            // ← agregado fresh() para datos actualizados
    }

    public function delete(string $uuid): void                    // ← int $id → string $uuid
    {
        $tipoRegistro = TipoRegistro::where('uuid', $uuid)        // ← findOrFail($id) → where + firstOrFail
            ->firstOrFail();

        $tipoRegistro->delete();
    }
}