<?php

namespace App\Http\Requests\Hseq;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuditoriaPreguntaRequest extends FormRequest
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
            'auditoria_id' => 'required|exists:auditorias,id',
            // El proceso ya no se elige al planificar (solo cláusula ISO + texto); se asigna
            // después, al ejecutar, con el endpoint de actualizar pregunta.
            'proceso_id' => 'nullable|exists:departamentos,id',
            // Una pregunta puede homologar varias normas/cláusulas a la vez.
            'clausulas_iso' => 'required|array|min:1',
            'clausulas_iso.*' => 'integer|exists:clausulas_iso,id',
            'pregunta' => 'required|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'auditoria_id.required' => 'La auditoría es obligatoria.',
            'auditoria_id.exists' => 'La auditoría especificada no existe.',
            'proceso_id.exists' => 'El proceso especificado no existe.',
            'clausulas_iso.required' => 'Selecciona al menos una cláusula ISO.',
            'clausulas_iso.min' => 'Selecciona al menos una cláusula ISO.',
            'clausulas_iso.*.exists' => 'Una de las cláusulas ISO seleccionadas no existe.',
            'pregunta.required' => 'La pregunta es obligatoria.',
            'pregunta.string' => 'La pregunta debe ser una cadena de texto.',
            'pregunta.max' => 'La pregunta no puede exceder los 1000 caracteres.',
        ];
    }
}
