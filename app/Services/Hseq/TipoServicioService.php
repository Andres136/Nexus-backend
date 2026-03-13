<?php

namespace App\Services\Hseq;

use App\Models\Hseq\TipoServicio;

class TipoServicioService
{
    //Crud para tipo de servicio

    public function create(array $data)
    {
        return TipoServicio::create($data);
    }

    public function find($id)
    {
        return TipoServicio::findOrFail($id);
    }


    public function update($id, array $data)
    {
        $tipoServicio = $this->find($id);
        $tipoServicio->update($data);
        return $tipoServicio;
    }

    public function delete($id)
    {
        $tipoServicio = $this->find($id);
        return $tipoServicio->delete();
    }

    //listar todos los tipos de servicios
public function all($search = null, $limit = 10)
{
    $query = TipoServicio::query();

    if ($search) {
        $query->where('nombre', 'like', "%{$search}%");
    }

    return $query->limit($limit)->get();
}  
}