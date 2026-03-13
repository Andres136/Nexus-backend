<?php

namespace App\Services\Hseq;

use App\Models\Hseq\TipoResiduo;

class TipoResiduoService
{
    public function create(array $data)
    {
        return TipoResiduo::create($data);
    }


    public function find($id)
    {
        return TipoResiduo::findOrFail($id);
    }



    public function update($id, array $data)
    {
        $tipoResiduo = $this->find($id);
        $tipoResiduo->update($data);
        return $tipoResiduo;
    }



    public function delete($id)
    {
        $tipoResiduo = $this->find($id);
        $tipoResiduo->delete();
        return true;
    }


    public function all($search = null, $limit = 10)
    {
        $query = TipoResiduo::query();
        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");
        }
        return $query->limit($limit)->get();
    }
}