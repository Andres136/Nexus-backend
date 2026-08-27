<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuditoriaPreguntaRequest extends FormRequest
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
            'proceso_id' => 'sometimes|required|exists:departamentos,id',
            'clausulas_iso' => 'sometimes|required|array|min:1',
            'clausulas_iso.*' => 'integer|exists:clausulas_iso,id',
            'pregunta' => 'sometimes|required|string|max:1000',
            // Personas del proceso asignado que fueron entrevistadas, para el informe.
            'personas_auditadas' => 'sometimes|array',
            'personas_auditadas.*' => 'integer|exists:users,id',
        ];
    }

    public function messages()
    {
        return [
            'proceso_id.required' => 'El proceso auditado es obligatorio.',
            'proceso_id.exists' => 'El proceso especificado no existe.',
            'clausulas_iso.required' => 'Selecciona al menos una cláusula ISO.',
            'clausulas_iso.min' => 'Selecciona al menos una cláusula ISO.',
            'clausulas_iso.*.exists' => 'Una de las cláusulas ISO seleccionadas no existe.',
            'personas_auditadas.*.exists' => 'Una de las personas seleccionadas no existe.',
            'pregunta.required' => 'La pregunta es obligatoria.',
            'pregunta.string' => 'La pregunta debe ser una cadena de texto.',
            'pregunta.max' => 'La pregunta no puede exceder los 1000 caracteres.',
        ];
    }
}
