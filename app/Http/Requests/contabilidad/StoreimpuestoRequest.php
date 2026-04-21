<?php

namespace App\Http\Requests\contabilidad;

use Illuminate\Foundation\Http\FormRequest;

class StoreimpuestoRequest extends FormRequest
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
            'nombre' => 'required|unique:impuestos,nombre|max:255',
            'porcentaje' => 'required|numeric|min:0|max:100',
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre del impuesto es obligatorio.',
            'nombre.unique' => 'El nombre del impuesto ya existe.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'porcentaje.required' => 'El porcentaje del impuesto es obligatorio.',
            'porcentaje.numeric' => 'El porcentaje debe ser un número.',
            'porcentaje.min' => 'El porcentaje no puede ser negativo.',
            'porcentaje.max' => 'El porcentaje no puede exceder el 100%.',
        ];
    }
}
