<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreRespuesteInspeccionRequest extends FormRequest
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
        'inspeccion_id' => 'required|integer|exists:inspecciones_hseq,id',

        'respuestas' => 'required|array|min:1',

        'respuestas.*.pregunta_inspeccion_id' => 
            'required|integer|exists:preguntas_inspecciones,id',

        'respuestas.*.respuesta' => 
            'required|boolean',

        'respuestas.*.observaciones' => 
            'nullable|string'
    ];
}


    public function messages()
    {
        return [
            'inspeccion_id.required' => 'El campo inspección es obligatorio.',
            'inspeccion_id.integer' => 'El campo inspección debe ser un número entero.',
            'inspeccion_id.exists' => 'La inspección seleccionada no existe.',

            'respuestas.required' => 'El campo respuestas es obligatorio.',
            'respuestas.array' => 'El campo respuestas debe ser un arreglo.',
            'respuestas.min' => 'Debe proporcionar al menos una respuesta.',

            'respuestas.*.pregunta_inspeccion_id.required' => 
                'El campo pregunta_inspeccion_id es obligatorio para cada respuesta.',
            'respuestas.*.pregunta_inspeccion_id.integer' => 
                'El campo pregunta_inspeccion_id debe ser un número entero para cada respuesta.',
            'respuestas.*.pregunta_inspeccion_id.exists' => 
                'La pregunta de inspección seleccionada no existe para cada respuesta.',

            'respuestas.*.respuesta.required' => 
                'El campo respuesta es obligatorio para cada respuesta.',
            'respuestas.*.respuesta.boolean' => 
                'El campo respuesta debe ser verdadero o falso para cada respuesta.',

            'respuestas.*.observaciones.string' => 
                'El campo observaciones debe ser una cadena de texto para cada respuesta.',
        ];
    }
}
