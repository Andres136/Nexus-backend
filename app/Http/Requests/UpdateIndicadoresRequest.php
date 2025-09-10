<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIndicadoresRequest extends FormRequest
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
            'departamento_id' => 'sometimes|required|exists:departamentos,id',
            'formula' => 'sometimes|required|string|max:255',
            'meta' => 'sometimes|required|numeric',
            'frecuencia' => 'sometimes|required|string|max:50',
            'nombre' => 'sometimes|required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
        ];
    }


    public function messages(): array
    {
        return [
            'departamento_id.required' => 'El campo departamento es obligatorio.',
            'departamento_id.exists' => 'El departamento seleccionado no existe.',
            'formula.required' => 'El campo fórmula es obligatorio.',
            'formula.string' => 'El campo fórmula debe ser una cadena de texto.',
            'formula.max' => 'El campo fórmula no debe exceder los 255 caracteres.',
            'meta.required' => 'El campo meta es obligatorio.',
            'meta.numeric' => 'El campo meta debe ser un número.',
            'frecuencia.required' => 'El campo frecuencia es obligatorio.',
            'frecuencia.string' => 'El campo frecuencia debe ser una cadena de texto.',
            'frecuencia.max' => 'El campo frecuencia no debe exceder los 50 caracteres.',
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 100 caracteres.',
            'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
            'descripcion.max' => 'El campo descripción no debe exceder los 255 caracteres.',
        ];
    }
}
