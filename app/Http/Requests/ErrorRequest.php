<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ErrorRequest extends FormRequest
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
            'descripcion' => 'required|string|max:100',
           'departamento_id' => 'required|integer',
        ];
    }
    public function messages(): array
    {
        return [
            'descripcion.required' => 'La descripción es requerida',
            'descripcion.string' => 'La descripción debe ser un texto',
            'descripcion.max' => 'La descripción no debe exceder los 100 caracteres',
            'departamento_id.required' => 'El proceso es requerido',
            'departamento_id.integer' => 'El proceso debe ser un número entero',
        ];
    }
}
