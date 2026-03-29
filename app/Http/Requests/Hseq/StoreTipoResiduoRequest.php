<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoResiduoRequest extends FormRequest
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
            'nombre' => 'required|unique:tipo_residuos,nombre|max:255',
            'descripcion' => 'nullable|string|max:1000',
        ];
    }


    public function messages()
    {
        return [
            'nombre.required' => 'El nombre del tipo de residuo es obligatorio.',
            'nombre.string' => 'El nombre del tipo de residuo debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del tipo de residuo no puede exceder los 255 caracteres.',
            'descripcion.string' => 'La descripción del tipo de residuo debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción del tipo de residuo no puede exceder los 1000 caracteres.',
        ];
    }
}
