<?php

namespace App\Services\RegistroDiario;

use App\Models\RegistroDiario\RegistroDiarios;

class RegistroDarioService
{
    public function crearRegistroDiario($data)
    {
        // Aquí puedes implementar la lógica para crear un nuevo registro diario
        // utilizando los modelos correspondientes (RegistroDiarios, Preguntas, etc.)
        // y cualquier validación necesaria.
        $registroDiario = RegistroDiarios::create([
            'usuario_id' => auth()->id(),
            'departamento_id' => $data['departamento_id'],
            'pregunta_id' => $data['pregunta_id'],
            'respuesta' => $data['respuesta'],
            'observaciones' => $data['observaciones'] ?? null,
            'fecha' => $data['fecha'],
            'tipo' => $data['tipo'],
        ]);

        return $registroDiario;
    }


    
}