<?php

namespace App\Services\contabilidad;

use App\Models\contabilidad\FormaPago;

class FormaPagoService
{


//LISTAR formas de pago
    public function listar()
    {
        return FormaPago::all();
    }
    //Crear forma de pago

    public function crear(array $data)
    {
       $save = FormaPago::create($data);
       return $save;

    }   

    //Actualizar forma de pago
    public function actualizar(int $id, array $data)
    {
        $formaPago = FormaPago::findOrFail($id);
        $formaPago->update($data);
        return $formaPago;
    }

//Eliminar forma de pago
    public function eliminar(int $id)
    {
        $formaPago = FormaPago::findOrFail($id);
        $formaPago->delete();
        return ['message' => 'Forma de pago eliminada correctamente'];
    }

    //Obtener forma de pago
    public function obtener(int $id)
    {
        $formaPago = FormaPago::findOrFail($id);
        return $formaPago;
    }
}