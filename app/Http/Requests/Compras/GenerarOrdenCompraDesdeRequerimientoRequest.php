<?php

namespace App\Http\Requests\Compras;

use Illuminate\Foundation\Http\FormRequest;

class GenerarOrdenCompraDesdeRequerimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'empresa_id' => 'required|integer|exists:empresas,id',
            'bodega_id' => 'nullable|integer|exists:bodegas,id',
            'fecha_entrega' => 'nullable|date',
            'observaciones' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'El ID del proveedor es obligatorio.',
            'proveedor_id.integer' => 'El ID del proveedor debe ser un número entero.',
            'proveedor_id.exists' => 'El ID del proveedor proporcionado no existe.',
            'empresa_id.required' => 'El ID de la empresa es obligatorio.',
            'empresa_id.integer' => 'El ID de la empresa debe ser un número entero.',
            'empresa_id.exists' => 'El ID de la empresa proporcionado no existe.',
            'bodega_id.integer' => 'El ID de la bodega debe ser un número entero.',
            'bodega_id.exists' => 'El ID de la bodega proporcionado no existe.',
            'fecha_entrega.date' => 'La fecha de entrega debe ser una fecha válida.',
            'observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
            'observaciones.max' => 'Las observaciones no deben exceder los 2000 caracteres.',
        ];
    }
}
