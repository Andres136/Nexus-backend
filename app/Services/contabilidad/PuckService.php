<?php

namespace App\Services\contabilidad;

use App\Models\contabilidad\Puck;

class PuckService
{
    //Crear puck

    public function listar()
    {
        return Puck::all();
    }   


    public function create (array $data)
    {
        // Lógica para crear un nuevo recurso
        $data['nombre'] = $data['nombre'];
        $data['numero'] =  $data['numero'];
        return  Puck::create($data);

    }

   public function update(array $data, int $id)
{
    $puck = Puck::findOrFail($id);

    $puck->update([
        'nombre' => $data['nombre'] ?? null,
        'numero' => $data['numero'] ?? null,
    ]);

    return $puck;
}

    public function delete (int $id)
    {
        // Lógica para eliminar un recurso
        $puck = Puck::findOrFail($id);
        $puck->delete();
       return $puck;
    }

    //tAER POER ID
    public function getById (int $id)
    {
        // Lógica para obtener un recurso por su ID
        return Puck::find($id);
        


    }   
}