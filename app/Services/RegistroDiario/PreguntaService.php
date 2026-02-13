<?php

namespace App\Services\RegistroDiario;

use App\Models\RegistroDiario\Preguntas;

class PreguntaService
{
    public function crearPregunta($data)
    {
        // Aquí puedes implementar la lógica para crear una nueva pregunta
        // utilizando el modelo correspondiente (Preguntas) y cualquier validación necesaria.

        
        $pregunta = Preguntas::create($data);

        return $pregunta;
    }
}