<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class EnviarRespuestasCuestionarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'respuestas' => 'required|array|min:1',
            'respuestas.*.pregunta_id' => 'required|integer',
            'respuestas.*.valor' => 'required|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'respuestas.required' => 'Debes responder el cuestionario antes de enviarlo.',
            'respuestas.*.valor.required' => 'No dejes preguntas sin responder.',
        ];
    }
}
