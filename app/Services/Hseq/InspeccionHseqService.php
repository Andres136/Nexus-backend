<?php

namespace App\Services\Hseq;

use App\Models\Hseq\InspeccionHseq;

class InspeccionHseqService
{
    //Crud para inspecciones

    public function create(array $data)
    {
        return InspeccionHseq::create($data);
    }   

    public function find($id)
    {
        return InspeccionHseq::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $inspeccion = $this->find($id);
        $inspeccion->update($data);
        return $inspeccion;
    }

    public function delete($id)
    {
        $inspeccion = $this->find($id);
        $inspeccion->delete();
        return $inspeccion;
    }

    //listar todas las inspecciones
    public function all($search = null, $limit = 10)
    {
        $query = InspeccionHseq::query();
        if ($search) {
            $query->where('observaciones', 'like', "%{$search}%");
        }
        return $query->limit($limit)->get();

    }
}
