<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class EncuestaRespuestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'respuestas'                  => 'required|array|min:1',
            'respuestas.*.pregunta_id'    => 'required|integer|exists:encuesta_preguntas,id',
            'respuestas.*.valor'          => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'respuestas.required'               => 'Debes responder al menos una pregunta',
            'respuestas.*.pregunta_id.required' => 'ID de pregunta requerido',
            'respuestas.*.pregunta_id.exists'   => 'La pregunta no existe',
            'respuestas.*.valor.required'       => 'La respuesta no puede estar vacía',
        ];
    }
}
