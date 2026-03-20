<?php

namespace App\Services\Hseq;

use App\Models\Hseq\InspeccionHseq;
use App\Models\Hseq\PreguntaInspeccion;
use App\Models\Hseq\RespuestaInspeccion;
use Illuminate\Support\Facades\DB;

class RespuestaInspeccionService
{
  public function create(array $data)
    {
        // Lógica para crear una respuesta de inspección

          return DB::transaction(function () use ($data) {

            foreach ($data['respuestas'] as $respuesta) {
                RespuestaInspeccion::create([
                    'inspeccion_id' => $data['inspeccion_id'],
                    'pregunta_inspeccion_id' => $respuesta['pregunta_inspeccion_id'],
                    'respuesta' => $respuesta['respuesta'],
                    'observaciones' => $respuesta['observaciones'] ?? null
                ]);
            }

            // Cambiar estado de inspección
            $inspeccion = InspeccionHseq::findOrFail($data['inspeccion_id']);

            $inspeccion->update([
                'estado' => 'finalizada'
            ]);

            return $inspeccion;
        });
    }

    public function find($id)
    {
        // Lógica para encontrar una respuesta de inspección por ID
        return RespuestaInspeccion::findOrFail($id);
    }

    public function update($id, array $data)
    {
        // Lógica para actualizar una respuesta de inspección
        $respuesta = $this->find($id);
        $respuesta->update($data);
        return $respuesta;  
    }

    public function delete($id)
    {
        // Lógica para eliminar una respuesta de inspección
        $respuesta = $this->find($id);
        $respuesta->delete();
        return $respuesta;
    }

    public function all($search = null, $limit = 100)
    {
        // Lógica para listar todas las respuestas de inspección con búsqueda y paginación
        $query = RespuestaInspeccion::query();
        if ($search) {
            $query->where('respuesta', 'like', "%{$search}%");
        }
        return $query->limit($limit)->get();
    }
}