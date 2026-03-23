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
$ultimoOrden = PreguntaInspeccion::where('tipo_inspeccion_id', $data['tipo_inspeccion_id'])
    ->max('orden') ?? 0;
        foreach ($data['preguntas'] as $pregunta) {

            $preguntas[] = PreguntaInspeccion::create([
                'tipo_inspeccion_id' => $data['tipo_inspeccion_id'],
                'pregunta' => $pregunta['pregunta'],
                'tipo_respuesta' => $pregunta['tipo_respuesta'],
                'orden' => $ultimoOrden + 1,
                'activa' => $pregunta['activa'] ?? true
            ]);
            $ultimoOrden++;
        }

        return $preguntas;
    });
}


public function findOne($id)
{
    return PreguntaInspeccion::findOrFail($id);
}

public function update($id, array $data)
{
    $pregunta = $this->findOne($id);
    $pregunta->update($data);
    return $pregunta;
}
    public function delete($id)
    {
        $preguntaInspeccion = $this->findOne($id);
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


public function listarPreguntasInspeccion(array $filtros)
{
 $query = PreguntaInspeccion::query([
        'id',
        'tipo_inspeccion_id',
        'pregunta',
        'tipo_respuesta',
        'orden',
        'activa'
    ])->with('tipoInspeccion:id,nombre');

  if(!empty($filtros['buscar'])) {
          $buscar = $filtros['buscar'];
            $query->where(function($q) use ($buscar) {
                $q->where('pregunta', 'like', "%{$buscar}%")
                    ->orWhereHas('tipoInspeccion', function($q2) use ($buscar) {
                        $q2->where('nombre', 'like', "%{$buscar}%");
                    });
            });

    }

    return $query->paginate($filtros['per_page'] ?? 20);
   
             
}
public function getByTipoInspeccion($tipoInspeccionId)
{
    
    return PreguntaInspeccion::select('id', 'tipo_inspeccion_id', 'pregunta', 'tipo_respuesta', )
        ->where('tipo_inspeccion_id', $tipoInspeccionId)
        ->where('activa', true)
        ->orderBy('orden')
        ->with('tipoInspeccion:id,nombre')
        ->get();
}
}