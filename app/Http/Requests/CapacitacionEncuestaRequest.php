<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CapacitacionEncuestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'capacitacion_uuid' => 'required_without:capacitacion_id|string|exists:capacitaciones,uuid',
            'capacitacion_id' => 'nullable|integer|exists:capacitaciones,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:5000',
            'estado' => 'nullable|in:activa,inactiva',
            'preguntas' => 'required|array|min:1',
            'preguntas.*.texto' => 'required|string|max:500',
            'preguntas.*.tipo' => 'required|in:texto,escala,opcion_multiple',
            'preguntas.*.opciones' => 'nullable|array',
            'preguntas.*.opciones.*' => 'string|max:255',
            'preguntas.*.orden' => 'nullable|integer|min:0|max:255',
            'preguntas.*.requerida' => 'nullable|boolean',
            'preguntas.*.max_escala' => 'nullable|integer|min:2|max:10',
            'preguntas.*.respuesta_correcta' => 'nullable|string|max:500',
        ];
    }
}
