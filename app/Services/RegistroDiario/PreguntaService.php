<?php

namespace App\Services\RegistroDiario;

use App\Models\RegistroDiario\Preguntas;
use Illuminate\Support\Facades\DB;

class PreguntaService
{
        public function crearPregunta(array $data)
    {
        return DB::transaction(function () use ($data) {

            $creadas = [];

            foreach ($data['preguntas'] as $item) {
                $creadas[] = Preguntas::create([
                    'departamento_id' => $data['departamento_id'],
                    'pregunta'        => $item['pregunta'],
                ]);
            }

            return $creadas;
        });
    }


        public function obtenerPreguntasPorDepartamento($departamentoId)
    {
        return Preguntas::where('departamento_id', $departamentoId)->get();
    }
}