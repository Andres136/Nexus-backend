<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class SugerirPreguntasAuditoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clausulas_iso' => 'required|array|min:1',
            'clausulas_iso.*' => 'integer|exists:clausulas_iso,id',
            // Proceso auditado = departamento (opcional): aterriza la IA en la documentación
            // y el historial de no conformidades del área.
            'departamento_id' => 'nullable|integer|exists:departamentos,id',
            'cantidad' => 'nullable|integer|between:3,10',
        ];
    }

    public function messages(): array
    {
        return [
            'clausulas_iso.required' => 'Selecciona al menos un requisito (cláusula ISO).',
            'clausulas_iso.min' => 'Selecciona al menos un requisito (cláusula ISO).',
            'clausulas_iso.*.exists' => 'Una de las cláusulas ISO seleccionadas no existe.',
            'departamento_id.exists' => 'El proceso (departamento) especificado no existe.',
            'cantidad.between' => 'La cantidad de preguntas debe estar entre 3 y 10.',
        ];
    }
}
