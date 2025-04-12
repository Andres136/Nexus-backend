<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class OrdenCompraProveedorRequest extends FormRequest
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
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha' => 'required|date',
            'numero_orden' => 'required|string|max:255|unique:orden_compra_proveedores,numero_orden',
            'observaciones' => 'required|string|max:1000',
            'detalles.*.descripcion' => 'required|string|max:255',
           'detalles.*.cantidad_solicitada' => 'required|numeric|min:0.01',
          'detalles.*.cantidad_entregada' => 'nullable|numeric|gte:0',


        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'El campo proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
            'fecha.required' => 'El campo fecha es obligatorio.',
            'fecha.date' => 'El campo fecha debe ser una fecha válida.',
            'numero_orden.required' => 'El campo número de orden es obligatorio.',
            'numero_orden.string' => 'El campo número de orden debe ser una cadena de texto.',
            'numero_orden.max' => 'El campo número de orden no puede tener más de 255 caracteres.',
            'numero_orden.unique' => 'El número de orden ya ha sido utilizado.',
            'observaciones.required'=>'Debes agregar una Observacion',
            'observaciones.string' => 'El campo observaciones debe ser una cadena de texto.',
            'observaciones.max' => 'El campo observaciones no puede tener más de 1000 caracteres.',
            'detalles.*.descripcion.required' => 'La descripción del detalle es obligatoria.',
            'detalles.*.descripcion.string' => 'La descripción del detalle debe ser una cadena de texto.',
            'detalles.*.descripcion.max' => 'La descripción del detalle no puede tener más de 255 caracteres.',
            'detalles.*.cantidad_solicitada.required' => 'La cantidad solicitada es obligatoria.',
            'detalles.*.cantidad_solicitada.numeric' => 'La cantidad solicitada debe ser un número.',
            'detalles.*.cantidad_solicitada.min' => 'La cantidad solicitada debe ser al menos 0.01.',
            'detalles.*.cantidad_entregada.numeric' => 'La cantidad entregada debe ser un número.',
            'detalles.*.cantidad_entregada.min' => 'La cantidad entregada debe ser al menos 0.',
            
        ];
    }
}
