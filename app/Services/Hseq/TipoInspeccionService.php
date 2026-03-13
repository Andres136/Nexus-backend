<?php

namespace App\Services\Hseq;

use App\Models\Hseq\TipoInspeccion;

class TipoInspeccionService
{
    //crud para tipo de inspeccion

    public function create(array $data)
    {
        return TipoInspeccion::create($data);
    }

    public function update(TipoInspeccion $tipoInspeccion, array $data)
    {
        $tipoInspeccion->update($data);
        return $tipoInspeccion;
    }

    public function delete(TipoInspeccion $tipoInspeccion)
    {
        return $tipoInspeccion->delete();
    }

public function find($id)
    {
        return TipoInspeccion::findOrFail($id);
    }

    public function all($search = null, $limit = 10)
    {
        $query = TipoInspeccion::query();

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");
        }

        return $query->limit($limit)->get();
    }

}