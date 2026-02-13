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
             'preguntas' => ['required', 'array', 'min:1'],
        'preguntas.*.pregunta' => ['required', 'string', 'min:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'departamento_id.required' => 'El campo departamento es obligatorio.',
            'departamento_id.exists' => 'El departamento seleccionado no existe.',
            'preguntas.required' => 'El campo preguntas es obligatorio.',
            'preguntas.array' => 'El campo preguntas debe ser un array.',
            'preguntas.min' => 'El campo preguntas debe tener al menos una pregunta.',
            'preguntas.*.pregunta.required' => 'El campo pregunta es obligatorio.',
            'preguntas.*.pregunta.string' => 'El campo pregunta debe ser una cadena de texto.',
            'preguntas.*.pregunta.min' => 'El campo pregunta debe tener al menos 5 caracteres.',
        ];
    }
}
