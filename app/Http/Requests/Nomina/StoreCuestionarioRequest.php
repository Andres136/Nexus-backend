<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreCuestionarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'duracion_segundos' => 'required|integer|min:30|max:7200',
            'preguntas' => 'required|array|min:1',
            'preguntas.*.texto' => 'required|string|max:1000',
            'preguntas.*.tipo' => 'required|in:texto,opcion_multiple',
            'preguntas.*.opciones' => 'nullable|array',
            'preguntas.*.opciones.*' => 'string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Debes indicar el título del cuestionario.',
            'duracion_segundos.required' => 'Debes indicar el tiempo para responder.',
            'duracion_segundos.min' => 'El tiempo mínimo es de 30 segundos.',
            'preguntas.required' => 'Agrega al menos una pregunta.',
            'preguntas.min' => 'Agrega al menos una pregunta.',
            'preguntas.*.texto.required' => 'Cada pregunta debe tener un texto.',
        ];
    }
}
