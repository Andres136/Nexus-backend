<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class EntregasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'detalle_id' => 'required|exists:orden_compra_proveedor_detalles,id',
    
            'cantidad_entregada' => 'nullable|numeric|min:0.01|required_with:fecha_entrega',
            'fecha_entrega'      => 'nullable|date|required_with:cantidad_entregada',
            'observaciones'      => 'nullable|string|max:255',
            'bodega_id'          => 'required|exists:bodegas,id',
            'producto_id'         => 'nullable|exists:products,id',



        ];
    }
    public function messages()
    {
        return [
            'detalle_id.required' => 'El campo detalle_id es obligatorio.',
            'detalle_id.exists' => 'El detalle_id no existe en la base de datos.',

            'cantidad_entregada.numeric' => 'La cantidad entregada debe ser un número.',
            'cantidad_entregada.min'     => 'La cantidad entregada debe ser mayor a 0.',
            'cantidad_entregada.required_with'
            => 'Debe indicar la cantidad si desea registrar una fecha de entrega.',

            'fecha_entrega.required_with'
            => 'Debe indicar la fecha cuando se registra una cantidad entregada.',
            'fecha_entrega.date'         => 'La fecha de entrega no tiene un formato válido.',

            'observaciones.string'       => 'Las observaciones deben ser texto.',
            'observaciones.max'          => 'Las observaciones no pueden exceder 255 caracteres.',


            'bodega_id.exists'           => 'La bodega seleccionada no existe.',
            'bodega_id.required'         => 'Debes seleccionar una bodega.',
            'producto_id.exists'         => 'El producto seleccionado no existe.',

        ];
    }
}
