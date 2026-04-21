<?php

namespace App\Services\contabilidad;

use App\Models\contabilidad\Impuesto;

class ImpuestoService
{
    //Crear impuesto
    

    //LISTAR impuestos
    public function listar()
    {
        return Impuesto::all();
    }


    public function crear(array $data)
    {
       $save = Impuesto::create($data);
       return $save;

    }   

    //Actualizar impuesto
    public function actualizar(int $id, array $data){
        $impuesto = Impuesto::findOrFail($id);  
        $impuesto->update($data);
                        return $impuesto;
                    }

    //Eliminar impuesto
    public function eliminar(int $id)
    {
        $impuesto = Impuesto::findOrFail($id);
        $impuesto->delete();
        return ['message' => 'Impuesto eliminado correctamente'];
    }

    //Obtener impuesto
    public function obtener(int $id)
    {
        $impuesto = Impuesto::findOrFail($id);
        return $impuesto;
    }   
}