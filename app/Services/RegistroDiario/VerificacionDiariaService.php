<?php

namespace App\Services\RegistroDiario;

use App\Models\RegistroDiario\Verificaciones;

class VerificacionDiariaService
{
    public function create($data)
    {
      $verificacionDiaria = Verificaciones::create([
          'registro_diario_id' => $data['registro_diario_id'],
          'pregunta_id' => $data['pregunta_id'],
          'usuario_id' => auth()->id(),
          'observaciones' => $data['observaciones'],
          'estado' => $data['estado'],
          'fecha' => now(),
      ]);

      return $verificacionDiaria;

    }


    
}