<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class ImportarClientesExelRequest extends FormRequest
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
            'clientes' => 'required|array',
            'clientes.*.nombre' => 'required|string|max:255',
            'clientes.*.email' => 'required|email|max:255',
            'clientes.*.telefono' => 'required|string|max:20',
            'clientes.*.direccion' => 'required|string|max:255',
            'clientes.*.nit' => 'required|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'clientes.required' => 'Se requiere un archivo de clientes.',
            'clientes.array' => 'El archivo de clientes debe ser un array.',
            'clientes.*.nombre.required' => 'El nombre es obligatorio.',
            'clientes.*.email.required' => 'El email es obligatorio.',
            'clientes.*.telefono.required' => 'El teléfono es obligatorio.',
            'clientes.*.direccion.required' => 'La dirección es obligatoria.',
            'clientes.*.nit.required' => 'El NIT o cédula es obligatorio.',
        ];
    }
}
