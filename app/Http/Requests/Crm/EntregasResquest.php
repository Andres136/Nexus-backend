<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class EntregasResquest extends FormRequest
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
            'cantidad_entregada' => 'required|numeric|min:0',
            'fecha_entrega' => 'required|date',
            'observaciones' => 'nullable|string|max:255',
        ];

    }
    public function messages()
    {
        return [
            'detalle_id.required' => 'El campo detalle_id es obligatorio.',
            'detalle_id.exists' => 'El detalle_id no existe en la base de datos.',
            'cantidad_entregada.required' => 'El campo cantidad_entregada es obligatorio.',
            'cantidad_entregada.numeric' => 'El campo cantidad_entregada debe ser un número.',
            'cantidad_entregada.min' => 'El campo cantidad_entregada debe ser mayor o igual a 0.',
            'fecha_entrega.required' => 'El campo fecha_entrega es obligatorio.',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.',
            'observaciones.max' => 'El campo observaciones no puede tener más de 255 caracteres.',
        ];
    }
}
