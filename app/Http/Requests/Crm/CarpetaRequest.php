<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class CarpetaRequest extends FormRequest
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
           'nombre' => 'required|string|max:255|unique:carpetas,nombre',

        ];
    }
    public function messages(): array
    {
        return [
            'nombre.required' => 'El campo nombre es requerido',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto',
            'nombre.unique' => 'El campo nombre ya se encuentra registrado',
            'nombre.max' => 'El campo nombre no debe exceder los 255 caracteres',
        ];
    }
}
