<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class ProveedorRequest extends FormRequest
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

            'nombre' => 'required|string|max:255',
            'nit' => 'required|string|max:255|unique:proveedores,nit',  
            'telefono' => 'nullable|string|max:255',
            'correo' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255',
         
            'observaciones' => 'nullable|string|max:255',
            
            
        ];



    }

    public function messages()
    {
        return [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nit.required' => 'El campo nit es obligatorio.',
            'nit.unique' => 'El nit ya está registrado.',
         
        ];
    }
}
