<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSedeRequest extends FormRequest
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
            'nombre' => 'required|string|max:255|unique:sedes,nombre,' . $this->route('sede')->id,
            'direccion' => 'nullable|string|max:255',
        
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.string' => 'El nombre debe ser una cadena de texto',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres',
            'nombre.unique' => 'El nombre ya está en uso',
            'direccion.string' => 'La dirección debe ser una cadena de texto',
            'direccion.max' => 'La dirección no puede tener más de 255 caracteres',

        ];
    }
}
