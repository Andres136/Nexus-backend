<?php

namespace App\Services\Hseq;

use App\Models\Hseq\NormaIso;

class NormaIsoService
{
    //crud para normas iso

    public function create(array $data)
    {
        return NormaIso::create($data);
    }

    public function update(NormaIso $normaIso, array $data)
    {
        $normaIso->update($data);
        return $normaIso;
    }

    public function delete(NormaIso $normaIso)
    {
        return $normaIso->delete();
    }

    public function find($id)
    {
        return NormaIso::findOrFail($id);
    }

    public function all($search = null, $limit = 100)
    {
        $query = NormaIso::query();

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");
        }

        return $query->orderBy('nombre')->limit($limit)->get();
    }

    // Listado paginado para la pantalla admin (all() se deja plano/sin paginar a propósito:
    // lo usan los selects de Planificar/Cláusulas ISO, que necesitan el catálogo completo).
    public function paginado($search = null, $perPage = 15)
    {
        $query = NormaIso::query();

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");
        }

        return $query->orderBy('nombre')->paginate($perPage);
    }
}
