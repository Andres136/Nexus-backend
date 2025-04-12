<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrdenCompraProveedorDetallesRequest extends FormRequest
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
            'detalles' => 'required|array|min:1',
            'detalles.*.id' => 'required|exists:orden_compra_proveedor_detalles,id',
         'detalles.*.cantidad_entregada' => 'nullable|numeric|gte:0',

    
        ];

    }

    public function messages(): array
    {
        return [
            'detalles.required' => 'El campo detalles es obligatorio.',
            'detalles.array' => 'El campo detalles debe ser un arreglo.',
            'detalles.min' => 'El campo detalles debe contener al menos un elemento.',
            'detalles.*.id.required' => 'El campo ID del detalle es obligatorio.',
            'detalles.*.id.exists' => 'El ID del detalle no existe en la base de datos.',
        
            'detalles.*.cantidad_entregada.numeric' => 'La cantidad entregada debe ser un número.',
            'detalles.*.cantidad_entregada.min' => 'La cantidad entregada debe ser al menos 0.01.',
        ];
    }
}
