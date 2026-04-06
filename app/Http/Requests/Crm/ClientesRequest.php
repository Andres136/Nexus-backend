<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class ClientesRequest extends FormRequest
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
            'nombre'   => 'required|string|max:255|unique:clientes,nombre'.$this->route('clientes'),
            'email'    => 'required|email|max:255',
            'telefono' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'nit' => 'required|string|max:255',
            'user_id' => 'required|integer',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es requerido',
            'nombre.unique' => 'El nombre ya existe',
            'email.required' => 'El email es requerido',
            'email.email' => 'El email no es valido',
            'email.unique' => 'El email ya existe',
            'telefono.required' => 'El telefono es requerido',
            'direccion.required' => 'La direccion es requerida',
            'nit.required' => 'El nit es requerido',
            'user_id.required' => 'El usuario es requerido',
        ];
    }
}
