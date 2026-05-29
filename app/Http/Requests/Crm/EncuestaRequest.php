<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class EncuestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo'                    => 'required|string|max:255',
            'descripcion'               => 'nullable|string',
            'estado'                    => 'nullable|in:activa,inactiva',
            'preguntas'                 => 'required|array|min:1',
            'preguntas.*.texto'         => 'required|string|max:500',
            'preguntas.*.tipo'          => 'required|in:texto,escala,opcion_multiple',
            'preguntas.*.opciones'      => 'required_if:preguntas.*.tipo,opcion_multiple|nullable|array|min:2',
            'preguntas.*.opciones.*'    => 'string|max:255',
            'preguntas.*.orden'         => 'nullable|integer|min:0',
            'preguntas.*.requerida'     => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required'                   => 'El título es requerido',
            'preguntas.required'                => 'La encuesta debe tener al menos una pregunta',
            'preguntas.min'                     => 'La encuesta debe tener al menos una pregunta',
            'preguntas.*.texto.required'        => 'El texto de la pregunta es requerido',
            'preguntas.*.tipo.required'         => 'El tipo de pregunta es requerido',
            'preguntas.*.tipo.in'               => 'El tipo debe ser: texto, escala u opcion_multiple',
            'preguntas.*.opciones.required_if'  => 'Las opciones son requeridas para preguntas de opción múltiple',
            'preguntas.*.opciones.min'          => 'Debe haber al menos 2 opciones',
        ];
    }
}
