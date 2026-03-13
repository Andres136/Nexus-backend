<?php

namespace App\Services\Hseq;

use App\Models\Hseq\PreguntaInspeccion;
use Illuminate\Support\Facades\DB;

class PreguntasInspeccionService
{
    //Crud para preguntas de inspección

public function create(array $data)
{
    return DB::transaction(function () use ($data) {

        $preguntas = [];

        foreach ($data['preguntas'] as $pregunta) {

            $preguntas[] = PreguntaInspeccion::create([
                'tipo_inspeccion_id' => $data['tipo_inspeccion_id'],
                'pregunta' => $pregunta['pregunta'],
                'orden' => $pregunta['orden'],
                'activa' => $pregunta['activa'] ?? true
            ]);
        }

        return $preguntas;
    });
}


public function find($id)
{
    return PreguntaInspeccion::where('tipo_inspeccion_id', $id)
        ->where('activa', 1)
        ->orderBy('orden')
        ->get();
}


    public function update($id, array $data)
    {
        $preguntaInspeccion = $this->find($id);
        $preguntaInspeccion->update($data);
        return $preguntaInspeccion;
    }

    public function delete($id)
    {
        $preguntaInspeccion = $this->find($id);
        return $preguntaInspeccion->delete();
    }


    //listar todas las preguntas de inspección
    public function all($search = null, $limit = 10)
    {
        $query = PreguntaInspeccion::query();
        if ($search) {
            $query->where('pregunta', 'like', "%{$search}%");
        }
        return $query->limit($limit)->get();

    }
}