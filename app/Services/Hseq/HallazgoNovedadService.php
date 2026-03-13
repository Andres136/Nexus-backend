<?php

namespace App\Services\Hseq;

use App\Models\Hseq\HallazgoNovedad;

class HallazgoNovedadService
{
   //crud para hallazgo novedad

   public function create(array $data)
    {
        return HallazgoNovedad::create($data);
    }

    public function find($id)
    {
        return HallazgoNovedad::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $hallazgoNovedad = $this->find($id);
        $hallazgoNovedad->update($data);
        return $hallazgoNovedad;
    }

    public function delete($id)
    {
        $hallazgoNovedad = $this->find($id);
        return $hallazgoNovedad->delete();
    }

}