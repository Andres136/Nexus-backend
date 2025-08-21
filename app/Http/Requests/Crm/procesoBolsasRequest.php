<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class procesoBolsasRequest extends FormRequest
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
            'nombre' => 'required|string|max:255|unique:proceso_bolsas,nombre',
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre del proceso es obligatorio.',
            'nombre.string' => 'El nombre del proceso debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del proceso no puede exceder los 255 caracteres.',
            'nombre.unique' => 'Ya existe un proceso con este nombre.',
        ];
    }
}
