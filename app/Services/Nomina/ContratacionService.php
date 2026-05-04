<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Contratacion;
use Illuminate\Support\Collection;

class ContratacionService
{
    public function getAll(): Collection
    {
        return Contratacion::with(['tipoContrato', 'usuario'])
            ->where('status', 1)
            ->get();
    }

    public function getById(int $id): Contratacion
    {
        return Contratacion::with(['tipoContrato', 'usuario'])
            ->findOrFail($id);
    }

    public function create(array $data): Contratacion
    {
        return Contratacion::create($data);
    }

    public function update(int $id, array $data): Contratacion
    {
        $contratacion = Contratacion::findOrFail($id);
        $contratacion->update($data);
        return $contratacion;
    }

    public function delete(int $id): void
    {
        $contratacion = Contratacion::findOrFail($id);
        $contratacion->delete();
    }
}