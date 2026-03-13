<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StorePreguntaInspeccionRequest extends FormRequest
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

        'tipo_inspeccion_id' => 'required|exists:tipo_inspecciones,id',

        'preguntas' => 'required|array|min:1',

        'preguntas.*.pregunta' => 'required|string|max:255',

        'preguntas.*.tipo_respuesta' => 'required|in:0,1',

        'preguntas.*.orden' => 'required|integer',

        'preguntas.*.activa' => 'nullable|boolean'
    ];
}public function messages()
{
    return [

        'tipo_inspeccion_id.required' => 'El tipo de inspección es obligatorio.',
        'tipo_inspeccion_id.exists' => 'El tipo de inspección seleccionado no existe.',

        'preguntas.required' => 'Debe enviar al menos una pregunta.',
        'preguntas.array' => 'Las preguntas deben enviarse en formato arreglo.',

        'preguntas.*.pregunta.required' => 'La pregunta es obligatoria.',
        'preguntas.*.pregunta.string' => 'La pregunta debe ser texto.',
        'preguntas.*.pregunta.max' => 'La pregunta no puede superar 255 caracteres.',

        'preguntas.*.tipo_respuesta.required' => 'El tipo de respuesta es obligatorio.',
        'preguntas.*.tipo_respuesta.in' => 'El tipo de respuesta debe ser 0 o 1.',

        'preguntas.*.orden.required' => 'El orden es obligatorio.',
        'preguntas.*.orden.integer' => 'El orden debe ser un número entero.',

        'preguntas.*.activa.boolean' => 'El campo activa debe ser verdadero o falso.'
    ];
}
}
