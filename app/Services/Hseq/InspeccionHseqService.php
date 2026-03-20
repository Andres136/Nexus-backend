<?php

namespace App\Services\Hseq;

use App\Models\Hseq\InspeccionHseq;

class InspeccionHseqService
{
    //Crud para inspecciones

    public function create(array $data)
    {
        return InspeccionHseq::create([
           'sede_id' => $data['sede_id'],
           'tipo_inspeccion_id' => $data['tipo_inspeccion_id'],
           'fecha' => $data['fecha'],
           'responsable_id' => auth()->id(),
           'estado' => $data['estado'] ?? 'pendiente',
           'observaciones' => $data['observaciones'] ?? null,
        ]);
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
public function all($search = null, $perPage = 100)
{
    $query = InspeccionHseq::with([
        'sede:id,nombre',
        'tipoInspeccion:id,nombre',
        'responsable:id,name'
    ]);

    if ($search) {
        $query->where('observaciones', 'like', "%{$search}%");
    }

    return $query->orderBy('fecha', 'desc')->paginate($perPage);
}
}
