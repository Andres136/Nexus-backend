<?php

namespace App\Services\Nomina;

use App\Models\Nomina\SeguridadSocial;
use Illuminate\Support\Collection;

class SeguridadSocialService
{
    public function getAll(): Collection
    {
        return SeguridadSocial::all();
    }

    public function getById(int $id): SeguridadSocial
    {
        return SeguridadSocial::findOrFail($id);
    }

    public function create(array $data): SeguridadSocial
    {
        return SeguridadSocial::create($data);
    }

    public function update(int $id, array $data): SeguridadSocial
    {
        $seguridadSocial = SeguridadSocial::findOrFail($id);
        $seguridadSocial->update($data);
        return $seguridadSocial;
    }

    public function delete(int $id): void
    {
        $seguridadSocial = SeguridadSocial::findOrFail($id);
        $seguridadSocial->delete();
    }
}