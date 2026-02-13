<?php

namespace App\Http\Requests\RegistroDiario;

use Illuminate\Foundation\Http\FormRequest;

class StorePreguntaRequest extends FormRequest
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
            'departamento_id' => 'required|exists:departamentos,id',
            'pregunta' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'departamento_id.required' => 'El campo departamento es obligatorio.',
            'departamento_id.exists' => 'El departamento seleccionado no existe.',
            'pregunta.required' => 'El campo pregunta es obligatorio.',
            'pregunta.string' => 'El campo pregunta debe ser una cadena de texto.',
        ];
    }
}
