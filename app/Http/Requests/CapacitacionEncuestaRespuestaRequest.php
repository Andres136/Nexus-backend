<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CapacitacionEncuestaRespuestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'respuestas' => 'required|array|min:1',
            'respuestas.*.pregunta_id' => 'required|integer|exists:capacitacion_encuesta_preguntas,id',
            'respuestas.*.valor' => 'required|string|max:5000',
        ];
    }
}
