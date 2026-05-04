<?php

namespace App\Services\Nomina;

use App\Models\Nomina\TipoContrato;
use Illuminate\Support\Collection;

class TipoContratoService
{
    public function getAll(): Collection
    {
        return TipoContrato::where('activo', true)->get();
    }

    public function getById(int $id): TipoContrato
    {
        return TipoContrato::findOrFail($id);
    }

    public function create(array $data): TipoContrato
    {
        return TipoContrato::create($data);
    }

    public function update(int $id, array $data): TipoContrato
    {
        $tipoContrato = TipoContrato::findOrFail($id);
        $tipoContrato->update($data);
        return $tipoContrato;
    }

    public function delete(int $id): void
    {
        $tipoContrato = TipoContrato::findOrFail($id);
        $tipoContrato->delete();
    }
}