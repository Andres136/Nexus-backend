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

    public function getById(int $id): TipoRegistro
    {
        return TipoRegistro::findOrFail($id);
    }

    public function create(array $data): TipoRegistro
    {
        return TipoRegistro::create($data);
    }

    public function update(int $id, array $data): TipoRegistro
    {
        $tipoRegistro = TipoRegistro::findOrFail($id);
        $tipoRegistro->update($data);
        return $tipoRegistro;
    }

    public function delete(int $id): void
    {
        $tipoRegistro = TipoRegistro::findOrFail($id);
        $tipoRegistro->delete();
    }
}
